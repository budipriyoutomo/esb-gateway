<?php

namespace Tests\Feature\PurchaseRequests;

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
use Tests\TestCase;

/**
 * Authorizing or rejecting a purchase request -- the gateway's first ESB write.
 *
 * PATCH /purchase/purchase-request/{purchaseRequestNum}/authorize|reject take
 * no body. No idempotency key is documented, so the rules that matter here
 * are: exactly one attempt, a definite failure reported as one, and an
 * ambiguous failure reported as "check before retrying" rather than as either
 * success or failure (docs/05-api-integration.md, Retry and Idempotency).
 */
class PurchaseRequestDecisionTest extends TestCase
{
    use RefreshDatabase;

    private const NUMBER = 'PR202603260005';

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
     * Only Super Admin holds purchase-requests.approve until the business
     * confirms which role approves purchasing (docs/06).
     */
    private function approver(): User
    {
        return $this->userWithRole(Role::SuperAdmin);
    }

    /**
     * @return array<string, mixed>
     */
    private function envelope(string $code = 'EC03100000', string $message = 'OK', string $status = 'ok'): array
    {
        return [
            'path' => 'https://stg7.esb.co.id/core-int/purchase/purchase-request/'.self::NUMBER.'/authorize',
            'timestamp' => '2026-03-26 10:30:00',
            'status' => $status,
            'code' => $code,
            'message' => $message,
            'result' => null,
            'errors' => null,
        ];
    }

    private function decide(User $user, string $decision, string $number = self::NUMBER): TestResponse
    {
        return $this->actingAs($user)->post(
            route('purchase-requests.decisions.store', $number),
            ['decision' => $decision],
        );
    }

    // ---------------------------------------------------------------- access

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->post(route('purchase-requests.decisions.store', self::NUMBER), ['decision' => 'authorize'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function reading_erp_data_is_not_enough_to_approve_a_purchase_request(): void
    {
        // esb.invoke lets an operator send reads to the ESB. Approving
        // purchasing is a business decision and needs its own permission.
        Http::fake();

        $this->decide($this->userWithRole(Role::IntegrationOperator), 'authorize')->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function a_viewer_may_not_approve(): void
    {
        Http::fake();

        $this->decide($this->userWithRole(Role::Viewer), 'reject')->assertForbidden();

        Http::assertNothingSent();
    }

    // --------------------------------------------------------------- success

    #[Test]
    public function an_approver_can_authorize_a_purchase_request(): void
    {
        Http::fake(['*' => Http::response($this->envelope())]);

        $approver = $this->approver();

        $this->decide($approver, 'authorize')
            ->assertRedirect(route('purchase-requests.show', self::NUMBER))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PATCH'
            && $request->url() === 'https://stg7.esb.co.id/core-int/purchase/purchase-request/'.self::NUMBER.'/authorize');

        $audit = AuditLog::query()->sole();
        $this->assertSame('purchase-request.authorized', $audit->action);
        $this->assertSame($approver->id, $audit->actor_id);
        $this->assertSame(self::NUMBER, $audit->metadata['number']);
    }

    #[Test]
    public function an_approver_can_reject_a_purchase_request(): void
    {
        Http::fake(['*' => Http::response($this->envelope())]);

        $this->decide($this->approver(), 'reject')
            ->assertRedirect(route('purchase-requests.show', self::NUMBER))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PATCH'
            && str_ends_with($request->url(), '/purchase/purchase-request/'.self::NUMBER.'/reject'));

        $this->assertSame('purchase-request.rejected', AuditLog::query()->sole()->action);
    }

    #[Test]
    public function the_write_is_recorded_in_the_integration_log(): void
    {
        Http::fake(['*' => Http::response($this->envelope())]);

        $this->decide($this->approver(), 'authorize');

        $this->assertSame('purchase-request.authorize', IntegrationLog::query()->sole()->operation);
    }

    // ------------------------------------------------------------ validation

    #[Test]
    public function an_unknown_decision_is_rejected_before_the_call(): void
    {
        Http::fake();

        $this->decide($this->approver(), 'delete')->assertSessionHasErrors('decision');

        Http::assertNothingSent();
    }

    #[Test]
    public function a_document_number_that_could_escape_the_path_never_reaches_the_esb(): void
    {
        Http::fake();

        $this->actingAs($this->approver())
            ->post('/purchase-requests/PR1%2F..%2Fdelete/decisions', ['decision' => 'authorize'])
            ->assertNotFound();

        Http::assertNothingSent();
    }

    // ------------------------------------------------------ definite failure

    #[Test]
    public function a_business_rejection_is_reported_as_a_failure_and_not_retried(): void
    {
        Http::fake(['*' => Http::response(
            $this->envelope('EC03100003', 'Validation Error', 'fail'),
            400,
        )]);

        $this->decide($this->approver(), 'authorize')
            ->assertRedirect(route('purchase-requests.show', self::NUMBER))
            ->assertSessionHas('inertia.flash_data.toast.type', 'error');

        Http::assertSentCount(1);

        // Nothing happened in the ERP, so nothing is recorded as decided.
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function an_unknown_document_number_is_a_404(): void
    {
        Http::fake(['*' => Http::response($this->envelope('EC03100004', 'Not Found', 'fail'), 400)]);

        $this->decide($this->approver(), 'authorize')->assertNotFound();

        $this->assertSame(0, AuditLog::query()->count());
    }

    // ------------------------------------------------------- unknown outcome

    #[Test]
    public function a_timeout_is_not_retried_and_is_reported_as_unconfirmed(): void
    {
        // A thrown transport failure is not recorded by Http::assertSent, so
        // count the attempts directly.
        $attempts = 0;
        Http::fake(function () use (&$attempts): never {
            $attempts++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $response = $this->decide($this->approver(), 'authorize');

        // Back to the detail page, which re-reads the document: that GET is
        // how the user finds out what actually happened before trying again.
        $response->assertRedirect(route('purchase-requests.show', self::NUMBER))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $attempts);

        $audit = AuditLog::query()->sole();
        $this->assertSame('purchase-request.decision-unconfirmed', $audit->action);
        $this->assertSame('authorize', $audit->metadata['decision']);
        $this->assertNotEmpty($audit->metadata['correlation_id']);
    }

    #[Test]
    public function an_ambiguous_upstream_error_is_reported_as_unconfirmed(): void
    {
        // EC03100032 "Unknown Error" is used for generic failures and some
        // business rejections alike, so it is not a definite "no".
        Http::fake(['*' => Http::response($this->envelope('EC03100032', 'Unknown Error', 'fail'), 500)]);

        $this->decide($this->approver(), 'reject')
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        Http::assertSentCount(1);
        $this->assertSame('purchase-request.decision-unconfirmed', AuditLog::query()->sole()->action);
    }

    // ------------------------------------------------------ double submission

    #[Test]
    public function a_second_decision_while_one_is_in_flight_is_refused(): void
    {
        Http::fake();

        // A double-click must not become two non-idempotent writes.
        $lock = Cache::lock('purchase-request-write:'.self::NUMBER, 60);
        $lock->get();

        try {
            $this->decide($this->approver(), 'authorize')
                ->assertRedirect(route('purchase-requests.show', self::NUMBER))
                ->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        Http::assertNothingSent();
    }
}
