<?php

namespace Tests\Feature\SimplePurchases;

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
use Tests\Fixtures\CoreSimplePurchase;
use Tests\TestCase;

/**
 * GET /purchase/simple-purchase/{cashPurchaseNum}, end to end.
 */
class SimplePurchaseDetailTest extends TestCase
{
    use RefreshDatabase;

    private const NUMBER = CoreSimplePurchase::NUMBER;

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
        $this->get(route('simple-purchases.show', self::NUMBER))->assertRedirect(route('login'));
    }

    #[Test]
    public function a_viewer_may_not_read_erp_data_through_the_gateway(): void
    {
        Http::fake();

        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('simple-purchases.show', self::NUMBER))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function an_integration_operator_can_view_a_simple_purchase(): void
    {
        Http::fake(['*' => Http::response(CoreSimplePurchase::detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('simple-purchases.show', self::NUMBER))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('simple-purchases/show')
                ->where('simplePurchase.number', self::NUMBER)
                ->where('simplePurchase.supplierName', 'Pasar Santa')
                ->where('simplePurchase.statusName', 'New')
                ->where('simplePurchase.createdByName', 'Andi Wijaya')
                ->where('simplePurchase.authorizedByName', null)
                ->where('simplePurchase.total', 255000)
                ->where('simplePurchase.paymentMethodName', 'Cash')
                ->has('simplePurchase.lines', 1)
                ->where('simplePurchase.lines.0.productName', 'Cabai merah')
                ->where('simplePurchase.lines.0.taxName', 'PPN 11%')
                ->has('simplePurchase.costs', 1)
                ->where('simplePurchase.costs.0.accountNumber', '6100')
                ->has('simplePurchase.payments', 1)
                ->where('simplePurchase.payments.0.number', 'PP202603200001'),
            );
    }

    #[Test]
    public function it_calls_the_documented_path_for_that_document_number(): void
    {
        Http::fake(['*' => Http::response(CoreSimplePurchase::detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('simple-purchases.show', self::NUMBER))
            ->assertOk();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://stg7.esb.co.id/core-int/purchase/simple-purchase/'.self::NUMBER);
    }

    #[Test]
    public function the_page_never_receives_vendor_storage_urls(): void
    {
        Http::fake(['*' => Http::response(CoreSimplePurchase::detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('simple-purchases.show', self::NUMBER))
            ->assertDontSee('esb.co.id');
    }

    #[Test]
    public function a_document_number_that_could_escape_the_path_never_reaches_the_esb(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get('/simple-purchases/CP1%2F..%2Fauthorize')
            ->assertNotFound();

        $this->actingAs($this->operator())
            ->get('/simple-purchases/CP1%3Fx%3D1')
            ->assertNotFound();

        Http::assertNothingSent();
    }

    #[Test]
    public function an_unknown_document_number_is_a_404(): void
    {
        Http::fake(['*' => Http::response(
            CoreSimplePurchase::writeEnvelope('EC03100004', 'Not Found', 'fail'),
            400,
        )]);

        $this->actingAs($this->operator())
            ->get(route('simple-purchases.show', 'CP404'))
            ->assertNotFound();

        Http::assertSentCount(1);
    }

    #[Test]
    public function the_call_is_recorded_in_the_integration_log(): void
    {
        Http::fake(['*' => Http::response(CoreSimplePurchase::detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('simple-purchases.show', self::NUMBER))
            ->assertOk();

        $this->assertSame('simple-purchase.view', IntegrationLog::query()->sole()->operation);
    }
}
