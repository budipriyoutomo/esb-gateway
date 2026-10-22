<?php

namespace Tests\Feature\PurchaseOrders;

use App\Authorization\Role;
use App\Models\AuditLog;
use App\Models\IntegrationLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CorePurchaseOrder;
use Tests\TestCase;

/**
 * Authorizing or rejecting a purchase order.
 *
 * PATCH /purchase/purchase-order/{purchaseNum}/authorize takes no body;
 * .../reject requires a body with the reason. Neither has a documented
 * idempotency key: exactly one attempt, a definite failure reported as one,
 * an ambiguous one as "check before retrying".
 */
class PurchaseOrderDecisionTest extends TestCase
{
    use RefreshDatabase;

    private const NUMBER = CorePurchaseOrder::NUMBER;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seed(RolePermissionSeeder::class);

        config()->set('esb.environment', 'staging_int');
        config()->set('esb.auth.mechanism', 'static_token');
        config()->set('esb.auth.static_token', 'a-static-api-key');
        config()->set('esb.retry.base_delay_ms', 0);
    }

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    /**
     * Only Super Admin holds purchase-orders.approve until the business
     * confirms which role approves purchase orders (docs/06).
     */
    private function approver(): User
    {
        return $this->userWithRole(Role::SuperAdmin);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function decide(User $user, string $decision, array $extra = [], string $number = self::NUMBER): TestResponse
    {
        return $this->actingAs($user)->post(
            route('purchase-orders.decisions.store', $number),
            ['decision' => $decision, ...$extra],
        );
    }

    // ---------------------------------------------------------------- access

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->post(route('purchase-orders.decisions.store', self::NUMBER), ['decision' => 'authorize'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function reading_erp_data_is_not_enough_to_approve_a_purchase_order(): void
    {
        Http::fake();

        $this->decide($this->userWithRole(Role::IntegrationOperator), 'authorize')->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function approving_other_purchasing_documents_does_not_grant_approving_orders(): void
    {
        Http::fake();

        $user = $this->userWithRole(Role::IntegrationOperator);
        $user->givePermissionTo('purchase-requests.approve', 'purchase-invoices.approve');

        $this->decide($user, 'authorize')->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function the_approve_permission_alone_is_not_enough_without_esb_invoke(): void
    {
        Http::fake();

        $user = $this->userWithRole(Role::Viewer);
        $user->givePermissionTo('purchase-orders.approve');

        $this->decide($user, 'authorize')->assertForbidden();

        Http::assertNothingSent();
    }

    // --------------------------------------------------------------- success

    #[Test]
    public function an_approver_can_authorize_a_purchase_order(): void
    {
        Http::fake(['*' => Http::response(CorePurchaseOrder::writeEnvelope())]);

        $approver = $this->approver();

        $this->decide($approver, 'authorize')
            ->assertRedirect(route('purchase-orders.show', self::NUMBER))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PATCH'
            && $request->url() === 'https://stg7.esb.co.id/core-int/purchase/purchase-order/'.self::NUMBER.'/authorize'
            && $request->data() === []);

        $audit = AuditLog::query()->sole();
        $this->assertSame('purchase-order.authorized', $audit->action);
        $this->assertSame($approver->id, $audit->actor_id);
        $this->assertSame(self::NUMBER, $audit->metadata['number']);
    }

    #[Test]
    public function a_reason_sent_with_an_authorize_is_not_forwarded(): void
    {
        Http::fake(['*' => Http::response(CorePurchaseOrder::writeEnvelope())]);

        $this->decide($this->approver(), 'authorize', ['reason' => 'ignored'])->assertRedirect();

        Http::assertSent(fn (Request $request): bool => $request->data() === []);
    }

    #[Test]
    public function an_approver_can_reject_a_purchase_order_with_a_reason(): void
    {
        Http::fake(['*' => Http::response(CorePurchaseOrder::writeEnvelope())]);

        $this->decide($this->approver(), 'reject', ['reason' => 'Price above the agreed pricelist'])
            ->assertRedirect(route('purchase-orders.show', self::NUMBER))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PATCH'
            && str_ends_with($request->url(), '/purchase/purchase-order/'.self::NUMBER.'/reject')
            && $request->data() === ['reason' => 'Price above the agreed pricelist']);

        $audit = AuditLog::query()->sole();
        $this->assertSame('purchase-order.rejected', $audit->action);
        $this->assertSame('Price above the agreed pricelist', $audit->metadata['reason']);
    }

    #[Test]
    public function the_write_is_recorded_in_the_integration_log(): void
    {
        Http::fake(['*' => Http::response(CorePurchaseOrder::writeEnvelope())]);

        $this->decide($this->approver(), 'reject', ['reason' => 'Wrong supplier']);

        $this->assertSame('purchase-order.reject', IntegrationLog::query()->sole()->operation);
    }

    // ------------------------------------------------------------ validation

    #[Test]
    public function an_unknown_decision_is_rejected_before_the_call(): void
    {
        Http::fake();

        $this->decide($this->approver(), 'close')->assertSessionHasErrors('decision');

        Http::assertNothingSent();
    }

    #[Test]
    public function a_rejection_without_a_reason_is_refused_before_the_call(): void
    {
        // The vendor marks the reject body as required.
        Http::fake();

        $this->decide($this->approver(), 'reject')->assertSessionHasErrors('reason');
        $this->decide($this->approver(), 'reject', ['reason' => '   '])->assertSessionHasErrors('reason');

        Http::assertNothingSent();
    }

    #[Test]
    public function an_overlong_reason_is_refused_before_the_call(): void
    {
        Http::fake();

        $this->decide($this->approver(), 'reject', ['reason' => str_repeat('x', 501)])
            ->assertSessionHasErrors('reason');

        Http::assertNothingSent();
    }

    #[Test]
    public function a_document_number_that_could_escape_the_path_never_reaches_the_esb(): void
    {
        Http::fake();

        $this->actingAs($this->approver())
            ->post('/purchase-orders/PO1%2F..%2Fdelete/decisions', ['decision' => 'authorize'])
            ->assertNotFound();

        Http::assertNothingSent();
    }

    // ------------------------------------------------------ definite failure

    #[Test]
    public function a_business_rejection_is_reported_as_a_failure_and_not_retried(): void
    {
        Http::fake(['*' => Http::response(
            CorePurchaseOrder::writeEnvelope('EC03100003', 'Validation Error', 'fail'),
            400,
        )]);

        $this->decide($this->approver(), 'authorize')
            ->assertRedirect(route('purchase-orders.show', self::NUMBER))
            ->assertSessionHas('inertia.flash_data.toast.type', 'error');

        Http::assertSentCount(1);
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function an_unknown_document_number_is_a_404(): void
    {
        Http::fake(['*' => Http::response(CorePurchaseOrder::writeEnvelope('EC03100004', 'Not Found', 'fail'), 400)]);

        $this->decide($this->approver(), 'authorize')->assertNotFound();

        $this->assertSame(0, AuditLog::query()->count());
    }

    // ------------------------------------------------------- unknown outcome

    #[Test]
    public function a_timeout_is_not_retried_and_is_reported_as_unconfirmed(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts): never {
            $attempts++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->decide($this->approver(), 'authorize')
            ->assertRedirect(route('purchase-orders.show', self::NUMBER))
            ->assertSessionHas('inertia.flash_data.unconfirmedWrite.reference');

        $this->assertSame(1, $attempts);

        $audit = AuditLog::query()->sole();
        $this->assertSame('purchase-order.write-unconfirmed', $audit->action);
        $this->assertSame('authorize', $audit->metadata['operation']);
        $this->assertSame(self::NUMBER, $audit->metadata['number']);
        $this->assertNotEmpty($audit->metadata['correlation_id']);
    }

    #[Test]
    public function an_ambiguous_upstream_error_is_reported_as_unconfirmed(): void
    {
        Http::fake(['*' => Http::response(CorePurchaseOrder::writeEnvelope('EC03100032', 'Unknown Error', 'fail'), 500)]);

        $this->decide($this->approver(), 'reject', ['reason' => 'Wrong supplier'])
            ->assertSessionHas('inertia.flash_data.unconfirmedWrite.reference');

        Http::assertSentCount(1);

        $audit = AuditLog::query()->sole();
        $this->assertSame('purchase-order.write-unconfirmed', $audit->action);
        $this->assertSame('reject', $audit->metadata['operation']);
    }

    // ------------------------------------------------------ double submission

    #[Test]
    public function a_second_decision_while_one_is_in_flight_is_refused(): void
    {
        Http::fake();

        $lock = Cache::lock('purchase-order-write:'.self::NUMBER, 60);
        $lock->get();

        try {
            $this->decide($this->approver(), 'authorize')
                ->assertRedirect(route('purchase-orders.show', self::NUMBER))
                ->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        Http::assertNothingSent();
    }
}
