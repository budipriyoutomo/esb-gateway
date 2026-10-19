<?php

namespace Tests\Feature\PurchaseOrders;

use App\Authorization\Role;
use App\Models\AuditLog;
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
 * DELETE /purchase/purchase-order/{purchaseNum}: no body, no documented
 * idempotency key, one attempt.
 */
class PurchaseOrderDeleteTest extends TestCase
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
     * Only Super Admin holds purchase-orders.manage until the business
     * confirms which role maintains purchase orders (docs/06).
     */
    private function manager(): User
    {
        return $this->userWithRole(Role::SuperAdmin);
    }

    private function destroy(User $user, string $number = self::NUMBER): TestResponse
    {
        return $this->actingAs($user)->delete(route('purchase-orders.destroy', $number));
    }

    #[Test]
    public function an_integration_operator_may_not_delete(): void
    {
        Http::fake();

        $this->destroy($this->userWithRole(Role::IntegrationOperator))->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function managing_other_purchasing_documents_does_not_grant_deleting_orders(): void
    {
        Http::fake();

        $user = $this->userWithRole(Role::IntegrationOperator);
        $user->givePermissionTo('purchase-requests.manage', 'purchase-invoices.manage');

        $this->destroy($user)->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function a_manager_can_delete_a_purchase_order(): void
    {
        Http::fake(['*' => Http::response(CorePurchaseOrder::writeEnvelope())]);

        $manager = $this->manager();

        $this->destroy($manager)
            ->assertRedirect(route('purchase-orders.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && $request->url() === 'https://stg7.esb.co.id/core-int/purchase/purchase-order/'.self::NUMBER);

        $audit = AuditLog::query()->sole();
        $this->assertSame('purchase-order.deleted', $audit->action);
        $this->assertSame($manager->id, $audit->actor_id);
        $this->assertSame(self::NUMBER, $audit->metadata['number']);
    }

    #[Test]
    public function a_refusal_returns_to_the_document_with_an_error(): void
    {
        Http::fake(['*' => Http::response(CorePurchaseOrder::writeEnvelope('EC03100003', 'Validation Error', 'fail'), 400)]);

        $this->destroy($this->manager())
            ->assertRedirect(route('purchase-orders.show', self::NUMBER))
            ->assertSessionHas('inertia.flash_data.toast.type', 'error');

        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function an_unconfirmed_delete_is_audited_and_lands_on_the_list(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts): never {
            $attempts++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->destroy($this->manager())
            ->assertRedirect(route('purchase-orders.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $attempts);

        $audit = AuditLog::query()->sole();
        $this->assertSame('purchase-order.write-unconfirmed', $audit->action);
        $this->assertSame('delete', $audit->metadata['operation']);
    }

    #[Test]
    public function a_delete_while_a_decision_is_in_flight_is_refused(): void
    {
        Http::fake();

        // Delete shares the per-document lock with authorize and reject.
        $lock = Cache::lock('purchase-order-write:'.self::NUMBER, 60);
        $lock->get();

        try {
            $this->destroy($this->manager())
                ->assertRedirect(route('purchase-orders.show', self::NUMBER))
                ->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function a_document_number_that_could_escape_the_path_never_reaches_the_esb(): void
    {
        Http::fake();

        $this->actingAs($this->manager())
            ->delete('/purchase-orders/PO1%2F..%2Fx')
            ->assertNotFound();

        Http::assertNothingSent();
    }
}
