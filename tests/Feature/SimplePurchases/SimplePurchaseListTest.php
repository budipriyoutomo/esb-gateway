<?php

namespace Tests\Feature\SimplePurchases;

use App\Authorization\Role;
use App\Models\IntegrationLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreSimplePurchase;
use Tests\TestCase;

/**
 * GET /purchase/simple-purchase, end to end: route, authorization, validation,
 * adapter, mapper, and Inertia page.
 */
class SimplePurchaseListTest extends TestCase
{
    use RefreshDatabase;

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

    // ---------------------------------------------------------------- access

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('simple-purchases.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function a_viewer_may_not_read_erp_data_through_the_gateway(): void
    {
        Http::fake();

        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('simple-purchases.index'))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function an_it_admin_may_not_invoke_esb_operations(): void
    {
        Http::fake();

        $this->actingAs($this->userWithRole(Role::ItAdmin))
            ->get(route('simple-purchases.index'))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function an_integration_operator_can_list_simple_purchases(): void
    {
        Http::fake(['*' => Http::response(CoreSimplePurchase::listResponse())]);

        $this->actingAs($this->operator())
            ->get(route('simple-purchases.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('simple-purchases/index')
                ->has('simplePurchases', 1)
                ->where('simplePurchases.0.number', CoreSimplePurchase::NUMBER)
                ->where('simplePurchases.0.supplierName', 'Pasar Santa')
                ->where('simplePurchases.0.supplierInvoiceNumber', 'NOTA-0331')
                ->where('simplePurchases.0.total', 255000.5)
                ->where('simplePurchases.0.currencySign', 'Rp')
                ->where('simplePurchases.0.statusName', 'New'),
            );
    }

    #[Test]
    public function it_calls_the_documented_core_path(): void
    {
        Http::fake(['*' => Http::response(CoreSimplePurchase::listResponse())]);

        $this->actingAs($this->operator())->get(route('simple-purchases.index'))->assertOk();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://stg7.esb.co.id/core-int/purchase/simple-purchase?'));
    }

    // --------------------------------------------------------------- filters

    #[Test]
    public function it_translates_filters_into_the_vendors_parameter_names(): void
    {
        Http::fake(['*' => Http::response(CoreSimplePurchase::listResponse())]);

        $this->actingAs($this->operator())
            ->get(route('simple-purchases.index', [
                'page' => 2,
                'limit' => 50,
                'number' => 'CP2026',
                'supplierInvoiceNumber' => 'NOTA',
                'dateFrom' => '2026-03-01',
                'dateTo' => '2026-03-31',
                'branchId' => 3,
                'search' => 'cabai',
            ]))
            ->assertOk();

        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $query === [
                'page' => '2',
                'limit' => '50',
                'cashPurchaseNum' => 'CP2026',
                'supplierInvoiceNum' => 'NOTA',
                'dateFrom' => '2026-03-01',
                'dateTo' => '2026-03-31',
                // Only a single branchID is documented for this list.
                'branchID' => '3',
                'indexSearch' => 'cabai',
            ];
        });
    }

    #[Test]
    public function it_sends_only_the_filters_that_were_supplied(): void
    {
        Http::fake(['*' => Http::response(CoreSimplePurchase::listResponse())]);

        $this->actingAs($this->operator())->get(route('simple-purchases.index'))->assertOk();

        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $query === ['page' => '1', 'limit' => '20'];
        });
    }

    #[Test]
    public function the_filters_are_echoed_back_to_the_page(): void
    {
        Http::fake(['*' => Http::response(CoreSimplePurchase::listResponse())]);

        $this->actingAs($this->operator())
            ->get(route('simple-purchases.index', ['search' => 'cabai', 'supplierInvoiceNumber' => 'NOTA']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters.search', 'cabai')
                ->where('filters.supplierInvoiceNumber', 'NOTA'),
            );
    }

    // ------------------------------------------------------------ pagination

    #[Test]
    public function the_page_never_receives_the_upstream_cursor_urls(): void
    {
        Http::fake(['*' => Http::response(CoreSimplePurchase::listResponse())]);

        $response = $this->actingAs($this->operator())->get(route('simple-purchases.index'));

        $response->assertDontSee('esb.co.id');
        $response->assertDontSee('stg7');
    }

    #[Test]
    public function pagination_is_re_derived_on_our_side(): void
    {
        Http::fake(['*' => Http::response(CoreSimplePurchase::listResponse(count: 45))]);

        $this->actingAs($this->operator())
            ->get(route('simple-purchases.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pagination.count', 45)
                ->where('pagination.lastPage', 3)
                ->where('pagination.hasMorePages', true),
            );
    }

    // ------------------------------------------------------------ validation

    #[Test]
    public function a_limit_above_the_esb_cap_is_rejected_before_the_call(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('simple-purchases.index', ['limit' => 101]))
            ->assertSessionHasErrors('limit');

        Http::assertNothingSent();
    }

    #[Test]
    public function a_date_not_in_the_documented_format_is_rejected_before_the_call(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('simple-purchases.index', ['dateFrom' => '20/03/2026', 'dateTo' => '2026/03/25']))
            ->assertSessionHasErrors(['dateFrom', 'dateTo']);

        Http::assertNothingSent();
    }

    #[Test]
    public function a_date_range_that_ends_before_it_starts_is_rejected(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('simple-purchases.index', [
                'dateFrom' => '2026-03-31',
                'dateTo' => '2026-03-01',
            ]))
            ->assertSessionHasErrors('dateTo');

        Http::assertNothingSent();
    }

    #[Test]
    public function the_branch_must_be_a_single_positive_integer(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('simple-purchases.index', ['branchId' => '3,4']))
            ->assertSessionHasErrors('branchId');

        $this->actingAs($this->operator())
            ->get(route('simple-purchases.index', ['branchId' => 0]))
            ->assertSessionHasErrors('branchId');

        Http::assertNothingSent();
    }

    // --------------------------------------------------------- upstream fail

    #[Test]
    public function an_upstream_failure_is_shown_safely(): void
    {
        Http::fake(['*' => Http::response([
            'path' => 'https://stg7.esb.co.id/core-int/purchase/simple-purchase',
            'timestamp' => '2026-03-20 10:30:00',
            'status' => 'fail',
            'code' => 'EC03100032',
            'message' => 'strconv.ParseInt: parsing "x": invalid syntax',
            'result' => null,
            'errors' => null,
        ], 500)]);

        $response = $this->actingAs($this->operator())->get(route('simple-purchases.index'));

        $response->assertStatus(502);
        $response->assertDontSee('strconv');
    }

    #[Test]
    public function a_timeout_is_reported_as_a_gateway_timeout(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));

        $this->actingAs($this->operator())
            ->get(route('simple-purchases.index'))
            ->assertStatus(504);
    }

    // ----------------------------------------------------------- observability

    #[Test]
    public function the_call_is_recorded_in_the_integration_log_against_the_user(): void
    {
        Http::fake(['*' => Http::response(CoreSimplePurchase::listResponse())]);

        $operator = $this->operator();

        $this->actingAs($operator)->get(route('simple-purchases.index'))->assertOk();

        $log = IntegrationLog::query()->sole();

        $this->assertSame('simple-purchase.list', $log->operation);
        $this->assertSame('success', $log->outcome);
        $this->assertSame($operator->id, $log->user_id);
    }
}
