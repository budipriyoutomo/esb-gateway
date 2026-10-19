<?php

namespace Tests\Feature\PurchaseOrders;

use App\Authorization\Role;
use App\Models\IntegrationLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CorePurchaseOrder;
use Tests\TestCase;

/**
 * GET /purchase/purchase-order/{purchaseNum}, end to end.
 */
class PurchaseOrderDetailTest extends TestCase
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

    private function operator(): User
    {
        return $this->userWithRole(Role::IntegrationOperator);
    }

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('purchase-orders.show', self::NUMBER))->assertRedirect(route('login'));
    }

    #[Test]
    public function a_viewer_may_not_read_erp_data_through_the_gateway(): void
    {
        Http::fake();

        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('purchase-orders.show', self::NUMBER))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function an_integration_operator_can_view_a_purchase_order(): void
    {
        Http::fake(['*' => Http::response(CorePurchaseOrder::detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('purchase-orders.show', self::NUMBER))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('purchase-orders/show')
                ->where('purchaseOrder.number', self::NUMBER)
                ->where('purchaseOrder.supplierName', 'PT Sumber Air')
                ->where('purchaseOrder.statusName', 'New')
                ->where('purchaseOrder.createdByName', 'Andi Wijaya')
                ->where('purchaseOrder.authorizedByName', null)
                ->where('purchaseOrder.total', 1110000)
                ->where('purchaseOrder.canDelete', false)
                ->has('purchaseOrder.lines', 1)
                ->where('purchaseOrder.lines.0.productName', 'Air mineral')
                ->where('purchaseOrder.lines.0.requestedQty', 180)
                ->has('purchaseOrder.goodsReceipts', 1)
                ->where('purchaseOrder.goodsReceipts.0.number', 'GR202603250002')
                ->has('purchaseOrder.purchaseInvoices', 1)
                ->where('purchaseOrder.purchaseInvoices.0.number', 'PI202603260005'),
            );
    }

    #[Test]
    public function it_calls_the_documented_path_for_that_document_number(): void
    {
        Http::fake(['*' => Http::response(CorePurchaseOrder::detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('purchase-orders.show', self::NUMBER))
            ->assertOk();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://stg7.esb.co.id/core-int/purchase/purchase-order/'.self::NUMBER);
    }

    #[Test]
    public function the_page_never_receives_vendor_storage_urls(): void
    {
        Http::fake(['*' => Http::response(CorePurchaseOrder::detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('purchase-orders.show', self::NUMBER))
            ->assertDontSee('esb.co.id');
    }

    #[Test]
    public function a_document_number_that_could_escape_the_path_never_reaches_the_esb(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get('/purchase-orders/PO1%2F..%2Fauthorize')
            ->assertNotFound();

        $this->actingAs($this->operator())
            ->get('/purchase-orders/PO1%3Fx%3D1')
            ->assertNotFound();

        Http::assertNothingSent();
    }

    #[Test]
    public function an_unknown_document_number_is_a_404(): void
    {
        Http::fake(['*' => Http::response(
            CorePurchaseOrder::writeEnvelope('EC03100004', 'Not Found', 'fail'),
            400,
        )]);

        $this->actingAs($this->operator())
            ->get(route('purchase-orders.show', 'PO404'))
            ->assertNotFound();

        Http::assertSentCount(1);
    }

    #[Test]
    public function the_call_is_recorded_in_the_integration_log(): void
    {
        Http::fake(['*' => Http::response(CorePurchaseOrder::detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('purchase-orders.show', self::NUMBER))
            ->assertOk();

        $this->assertSame('purchase-order.view', IntegrationLog::query()->sole()->operation);
    }
}
