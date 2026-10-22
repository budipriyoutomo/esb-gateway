<?php

namespace Tests\Feature\Lookups;

use App\Authorization\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreLookup;
use Tests\Fixtures\OmsGetMenu;
use Tests\Fixtures\OmsMenuCategory;
use Tests\TestCase;

/**
 * GET /lookups/{kind} — JSON for searchable pickers ([ESB-019]).
 */
class LookupTest extends TestCase
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

    /**
     * The query string the gateway sent upstream.
     *
     * @return array<string, mixed>
     */
    private static function sentQuery(Request $request): array
    {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return $query;
    }

    private function operator(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::IntegrationOperator->value);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function lookup(string $kind, array $query = [], ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->operator())
            ->getJson(route('lookups.show', ['kind' => $kind, ...$query]));
    }

    // ---------------------------------------------------------------- access

    #[Test]
    public function guests_are_refused(): void
    {
        $this->getJson(route('lookups.show', 'branches'))->assertUnauthorized();
    }

    #[Test]
    public function reading_erp_data_requires_esb_invoke(): void
    {
        Http::fake();
        $viewer = User::factory()->create();
        $viewer->assignRole(Role::Viewer->value);

        $this->lookup('branches', user: $viewer)->assertForbidden();
        Http::assertNothingSent();
    }

    #[Test]
    public function an_unknown_kind_is_a_404(): void
    {
        Http::fake();

        $this->lookup('customers')->assertNotFound();
        Http::assertNothingSent();
    }

    // ------------------------------------------------------- whole-list kinds

    #[Test]
    public function branches_come_from_the_active_branch_list(): void
    {
        Http::fake(['*' => Http::response(CoreLookup::listResponse(CoreLookup::branches()))]);

        $this->lookup('branches')
            ->assertOk()
            ->assertExactJson([
                'options' => [
                    ['value' => '1', 'label' => 'Jakarta Pusat', 'code' => 'JKT', 'meta' => []],
                    ['value' => '2', 'label' => 'Bandung', 'code' => 'BDG', 'meta' => []],
                    ['value' => '3', 'label' => 'Surabaya', 'code' => 'SBY', 'meta' => []],
                ],
                'hasMore' => false,
            ]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_contains($request->url(), '/branch?')
            && (self::sentQuery($request)['flagActive'] ?? null) === '1');
    }

    #[Test]
    public function a_whole_list_is_searched_in_the_gateway(): void
    {
        Http::fake(['*' => Http::response(CoreLookup::listResponse(CoreLookup::suppliers(), '/supplier/list'))]);

        $this->lookup('suppliers', ['search' => 'maju'])
            ->assertOk()
            ->assertJsonPath('options.0.label', 'CV Maju Jaya')
            ->assertJsonCount(1, 'options');

        // The search is not sent: /supplier/list documents no name filter.
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/supplier/list')
            && ! str_contains($request->url(), 'maju'));
    }

    #[Test]
    public function locations_are_narrowed_to_a_branch_without_sending_an_array_filter(): void
    {
        Http::fake(['*' => Http::response(CoreLookup::listResponse(CoreLookup::locations(), '/location/list'))]);

        $this->lookup('locations', ['branchId' => 1])
            ->assertOk()
            ->assertJsonCount(2, 'options')
            ->assertJsonPath('options.1.label', 'Dapur');

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/location/list')
            && (self::sentQuery($request)['flagActive'] ?? null) === '1'
            && ! str_contains($request->url(), 'branchID'));
    }

    #[Test]
    public function cash_and_bank_accounts_are_narrowed_to_a_branch(): void
    {
        Http::fake(['*' => Http::response(CoreLookup::listResponse(CoreLookup::cashBankAccounts(), '/accounting/coa/cash-bank'))]);

        $this->lookup('cash-bank-accounts', ['branchId' => 2])
            ->assertOk()
            ->assertExactJson([
                'options' => [['value' => '1201', 'label' => 'Kas Bandung', 'code' => '1201', 'meta' => ['branchId' => 2]]],
                'hasMore' => false,
            ]);
    }

    #[Test]
    public function statuses_list_every_erp_status(): void
    {
        Http::fake(['*' => Http::response(CoreLookup::listResponse(CoreLookup::statuses(), '/common/status'))]);

        $this->lookup('statuses')->assertOk()->assertJsonCount(3, 'options');
    }

    #[Test]
    public function metrics_category_types_and_taxes_come_from_their_common_lists(): void
    {
        Http::fake([
            '*/common/metrics*' => Http::response(CoreLookup::listResponse(CoreLookup::metrics(), '/common/metrics')),
            '*/common/category-types/list*' => Http::response(CoreLookup::listResponse(CoreLookup::categoryTypes(), '/common/category-types/list')),
            '*/tax/list*' => Http::response(CoreLookup::listResponse(CoreLookup::taxes(), '/tax/list')),
        ]);

        $this->lookup('metrics')->assertOk()->assertJsonCount(2, 'options');
        $this->lookup('category-types')->assertOk()->assertJsonPath('options.1.label', 'Asset');
        $this->lookup('taxes', ['search' => 'pb'])->assertOk()
            ->assertJsonPath('options', [['value' => '3', 'label' => 'PB1', 'code' => null, 'meta' => ['rate' => 10]]]);
    }

    #[Test]
    public function product_reference_lists_come_from_their_whole_lists(): void
    {
        $sent = [];
        Http::fake(function (Request $request) use (&$sent) {
            $sent[] = parse_url($request->url(), PHP_URL_PATH).'?'.http_build_query(self::sentQuery($request));

            return match (true) {
                str_contains($request->url(), '/product/category/all') => Http::response(CoreLookup::listResponse(CoreLookup::productCategories(), '/product/category/all')),
                str_contains($request->url(), '/product/sub-category/all') => Http::response(CoreLookup::listResponse(CoreLookup::productSubCategories(), '/product/sub-category/all')),
                str_contains($request->url(), '/units/list') => Http::response(CoreLookup::listResponse(CoreLookup::units(), '/units/list')),
                default => Http::response(CoreLookup::listResponse(CoreLookup::currencies(), '/common/currency/active')),
            };
        });

        $this->lookup('product-categories')->assertOk()->assertJsonCount(2, 'options');
        $this->lookup('product-sub-categories')->assertOk()->assertJsonCount(1, 'options');
        $this->lookup('units', ['search' => 'sa'])->assertOk()->assertJsonPath('options.0.label', 'SAK');
        $this->lookup('currencies')->assertOk()->assertJsonPath('options.1.code', 'USD');

        $this->assertSame([
            '/core-int/product/category/all?',
            '/core-int/product/sub-category/all?flagActive=1',
            '/core-int/units/list?flagActive=1',
            '/core-int/common/currency/active?',
        ], $sent);
    }

    #[Test]
    public function product_details_are_searched_by_name_in_the_erp(): void
    {
        $sent = null;
        Http::fake(function (Request $request) use (&$sent) {
            $sent = self::sentQuery($request);

            return Http::response(CoreLookup::pageResponse(CoreLookup::productDetails(), 30, 1, 20, '/product-detail/browse'));
        });

        $this->lookup('product-details', ['search' => 'beras'])
            ->assertOk()
            ->assertJsonPath('options.0.value', '501')
            ->assertJsonPath('hasMore', true);

        $this->assertSame(['productName' => 'beras', 'page' => '1', 'limit' => '20'], $sent);
    }

    #[Test]
    public function active_purposes_are_searched_by_name_in_the_erp(): void
    {
        $sent = null;
        Http::fake(function (Request $request) use (&$sent) {
            $sent = self::sentQuery($request);

            return Http::response(CoreLookup::pageResponse(CoreLookup::purposes(), 2, 1, 20, '/purpose'));
        });

        $this->lookup('purposes', ['search' => 'waste'])
            ->assertOk()
            ->assertJsonPath('options.0.value', '7')
            ->assertJsonCount(1, 'options')
            ->assertJsonPath('hasMore', false);

        $this->assertSame(['purposeName' => 'waste', 'flagActive' => '1', 'page' => '1', 'limit' => '20'], $sent);
    }

    // ------------------------------------------------------------- OMS kinds

    #[Test]
    public function menu_categories_are_read_page_by_page_from_the_oms(): void
    {
        $sent = [];
        Http::fake(function (Request $request) use (&$sent) {
            $sent[] = $request->url();

            return Http::response(OmsMenuCategory::list([
                OmsMenuCategory::row(),
                OmsMenuCategory::row(['menuCategoryID' => 17, 'menuCategoryName' => 'Drink-Int', 'menuCategoryCode' => 'DINT']),
            ], page: 1, count: 16));
        });

        // The vendor has no search: the page is searched in the gateway.
        $this->lookup('menu-categories', ['search' => 'drink'])
            ->assertOk()
            ->assertJsonCount(1, 'options')
            ->assertJsonPath('options.0.value', '17')
            ->assertJsonPath('options.0.label', 'Drink-Int')
            ->assertJsonPath('options.0.code', 'DINT')
            ->assertJsonPath('hasMore', true);

        $this->assertSame(['https://stg7.esb.co.id/api-fnb-backend-int/web/corev1/master/get-menu-category?page=1'], $sent);
    }

    #[Test]
    public function menu_category_details_are_named_with_their_category(): void
    {
        Http::fake(['*' => Http::response(OmsMenuCategory::list([OmsMenuCategory::row()], page: 1, count: 1))]);

        $this->lookup('menu-category-details')
            ->assertOk()
            ->assertJsonPath('options.0.value', '34')
            ->assertJsonPath('options.0.label', 'Food-Int › Main Course-Int')
            ->assertJsonPath('hasMore', false);
    }

    #[Test]
    public function pos_menus_are_read_from_the_oms_master_menu_list(): void
    {
        $sent = null;
        Http::fake(function (Request $request) use (&$sent) {
            $sent = $request->url();

            return Http::response(OmsGetMenu::response());
        });

        $this->lookup('pos-menus', ['page' => 2])
            ->assertOk()
            ->assertJsonPath('options.0.value', '5564')
            ->assertJsonPath('options.0.label', 'Paket Separate')
            ->assertJsonPath('options.0.code', 'PS01');

        $this->assertSame('https://stg7.esb.co.id/api-fnb-backend-int/web/corev1/master/get-menu?page=2', $sent);
    }

    // ------------------------------------------------------ ESB-paginated kinds

    #[Test]
    public function account_search_by_number_or_description_is_sent_to_the_erp(): void
    {
        Http::fake(['*' => Http::response(CoreLookup::pageResponse(CoreLookup::coaAccounts(), count: 45, page: 2))]);

        $this->lookup('coa-accounts', ['search' => '61', 'page' => 2])
            ->assertOk()
            ->assertJsonPath('options.0.value', '6101')
            ->assertJsonPath('hasMore', true);

        $this->lookup('coa-accounts', ['search' => 'listrik']);

        Http::assertSent(function (Request $request): bool {
            $query = self::sentQuery($request);

            return ($query['coaNo'] ?? null) === '61'
                && ($query['page'] ?? null) === '2'
                && ! isset($query['description']);
        });
        Http::assertSent(function (Request $request): bool {
            $query = self::sentQuery($request);

            return ($query['description'] ?? null) === 'listrik' && ! isset($query['coaNo']);
        });
    }

    #[Test]
    public function transfer_products_need_a_branch(): void
    {
        Http::fake();

        $this->lookup('transfer-products', ['search' => 'beras'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('branchId');

        Http::assertNothingSent();
    }

    #[Test]
    public function transfer_products_are_searched_by_name_for_the_branch(): void
    {
        Http::fake(['*' => Http::response(CoreLookup::pageResponse(CoreLookup::transferProducts(), count: 1, path: '/product/browse-transfer'))]);

        $this->lookup('transfer-products', ['branchId' => 1, 'search' => 'beras'])
            ->assertOk()
            ->assertJsonPath('options.0.meta.uomName', 'KG')
            ->assertJsonPath('hasMore', false);

        Http::assertSent(function (Request $request): bool {
            $query = self::sentQuery($request);

            return str_contains($request->url(), '/product/browse-transfer')
                && ($query['branchID'] ?? null) === '1'
                && ($query['productName'] ?? null) === 'beras';
        });
    }

    // ------------------------------------------------------------- validation

    #[Test]
    public function the_query_is_bounded(): void
    {
        Http::fake();

        $this->lookup('branches', ['search' => str_repeat('x', 101)])->assertJsonValidationErrors('search');
        $this->lookup('branches', ['page' => 0])->assertJsonValidationErrors('page');
        $this->lookup('branches', ['branchId' => 'abc'])->assertJsonValidationErrors('branchId');

        Http::assertNothingSent();
    }

    // ---------------------------------------------------------------- failure

    #[Test]
    public function an_erp_failure_is_a_json_error_without_the_raw_message(): void
    {
        Http::fake(['*' => Http::response([
            'status' => 'fail',
            'code' => 'EC03100032',
            'message' => 'SQLSTATE[42S22] internal detail',
            'result' => null,
        ], 500)]);

        $response = $this->lookup('branches');

        $response->assertStatus(502)->assertJsonStructure(['message', 'correlationId']);
        $this->assertStringNotContainsString('SQLSTATE', (string) $response->getContent());
    }
}
