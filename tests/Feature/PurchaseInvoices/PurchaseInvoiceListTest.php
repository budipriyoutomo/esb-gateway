<?php

namespace Tests\Feature\PurchaseInvoices;

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
use Tests\TestCase;

/**
 * GET /purchase/purchase-invoices, end to end: route, authorization,
 * validation, adapter, mapper, and Inertia page.
 */
class PurchaseInvoiceListTest extends TestCase
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

    /**
     * @return array<string, mixed>
     */
    private function listResponse(int $count = 45): array
    {
        return [
            'path' => 'https://stg7.esb.co.id/core-int/purchase/purchase-invoices',
            'timestamp' => '2026-03-26 10:30:00',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => [
                'page' => 1,
                'limit' => 20,
                'count' => $count,
                'data' => [
                    [
                        'additionalInfo' => 'March supplies',
                        'branchID' => 3,
                        'branchName' => 'Outlet Senopati',
                        'createdBy' => 'andi',
                        'createdByFullName' => 'Andi Wijaya',
                        'currencyID' => 1,
                        'currencySign' => 'Rp',
                        'invoiceTotal' => 1250000.5,
                        'linkInvoiceNumEsbGoods' => '',
                        'overDue' => 0,
                        'purchaseInvoiceDate' => '2026-03-26',
                        'purchaseInvoiceDueDate' => '2026-04-25',
                        'purchaseInvoiceNum' => 'PI202603260005',
                        'statusID' => 1,
                        'statusName' => 'Open',
                        'supplierID' => 14,
                        'supplierInvoiceNum' => 'INV/SUP/0331',
                        'supplierName' => 'PT Sumber Air',
                    ],
                ],
                'prev' => '',
                'next' => 'https://stg7.esb.co.id/core-int/purchase/purchase-invoices?page=2&limit=20',
            ],
            'errors' => null,
        ];
    }

    // ---------------------------------------------------------------- access

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('purchase-invoices.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function a_viewer_may_not_read_erp_data_through_the_gateway(): void
    {
        Http::fake();

        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('purchase-invoices.index'))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function an_it_admin_may_not_invoke_esb_operations(): void
    {
        Http::fake();

        $this->actingAs($this->userWithRole(Role::ItAdmin))
            ->get(route('purchase-invoices.index'))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function an_integration_operator_can_list_purchase_invoices(): void
    {
        Http::fake(['*' => Http::response($this->listResponse())]);

        $this->actingAs($this->operator())
            ->get(route('purchase-invoices.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('purchase-invoices/index')
                ->has('purchaseInvoices', 1)
                ->where('purchaseInvoices.0.number', 'PI202603260005')
                ->where('purchaseInvoices.0.supplierName', 'PT Sumber Air')
                ->where('purchaseInvoices.0.supplierInvoiceNumber', 'INV/SUP/0331')
                ->where('purchaseInvoices.0.total', 1250000.5)
                ->where('purchaseInvoices.0.currencySign', 'Rp')
                ->where('purchaseInvoices.0.statusName', 'Open'),
            );
    }

    #[Test]
    public function it_calls_the_documented_core_path(): void
    {
        Http::fake(['*' => Http::response($this->listResponse())]);

        $this->actingAs($this->operator())->get(route('purchase-invoices.index'))->assertOk();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://stg7.esb.co.id/core-int/purchase/purchase-invoices?'));
    }

    // --------------------------------------------------------------- filters

    #[Test]
    public function it_translates_filters_into_the_vendors_parameter_names(): void
    {
        Http::fake(['*' => Http::response($this->listResponse())]);

        $this->actingAs($this->operator())
            ->get(route('purchase-invoices.index', [
                'page' => 2,
                'limit' => 50,
                'number' => 'PI2026',
                'supplierInvoiceNumber' => 'INV/SUP',
                'dateFrom' => '2026-03-01',
                'dateTo' => '2026-03-31',
                'branchIds' => [3, 8],
                'search' => 'air',
            ]))
            ->assertOk();

        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $query === [
                'page' => '2',
                'limit' => '50',
                'purchaseInvoiceNum' => 'PI2026',
                'supplierInvoiceNum' => 'INV/SUP',
                'dateFrom' => '2026-03-01',
                'dateTo' => '2026-03-31',
                // Documented as form style, explode false: one comma-separated value.
                'branchIDs' => '3,8',
                'indexSearch' => 'air',
            ];
        });
    }

    #[Test]
    public function it_sends_only_the_filters_that_were_supplied(): void
    {
        Http::fake(['*' => Http::response($this->listResponse())]);

        $this->actingAs($this->operator())->get(route('purchase-invoices.index'))->assertOk();

        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $query === ['page' => '1', 'limit' => '20'];
        });
    }

    #[Test]
    public function the_filters_are_echoed_back_to_the_page(): void
    {
        Http::fake(['*' => Http::response($this->listResponse())]);

        $this->actingAs($this->operator())
            ->get(route('purchase-invoices.index', ['search' => 'air', 'dateFrom' => '2026-03-01']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters.search', 'air')
                ->where('filters.dateFrom', '2026-03-01'),
            );
    }

    // ------------------------------------------------------------ pagination

    #[Test]
    public function the_page_never_receives_the_upstream_cursor_urls(): void
    {
        Http::fake(['*' => Http::response($this->listResponse())]);

        $response = $this->actingAs($this->operator())->get(route('purchase-invoices.index'));

        $response->assertDontSee('esb.co.id');
        $response->assertDontSee('stg7');
    }

    #[Test]
    public function pagination_is_re_derived_on_our_side(): void
    {
        Http::fake(['*' => Http::response($this->listResponse(count: 45))]);

        $this->actingAs($this->operator())
            ->get(route('purchase-invoices.index'))
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
            ->get(route('purchase-invoices.index', ['limit' => 101]))
            ->assertSessionHasErrors('limit');

        Http::assertNothingSent();
    }

    #[Test]
    public function a_date_not_in_the_documented_format_is_rejected_before_the_call(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('purchase-invoices.index', ['dateFrom' => '26/03/2026']))
            ->assertSessionHasErrors('dateFrom');

        Http::assertNothingSent();
    }

    #[Test]
    public function a_date_range_that_ends_before_it_starts_is_rejected(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('purchase-invoices.index', ['dateFrom' => '2026-03-31', 'dateTo' => '2026-03-01']))
            ->assertSessionHasErrors('dateTo');

        Http::assertNothingSent();
    }

    #[Test]
    public function branch_ids_must_be_positive_integers(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('purchase-invoices.index', ['branchIds' => ['3,4']]))
            ->assertSessionHasErrors('branchIds.0');

        Http::assertNothingSent();
    }

    // --------------------------------------------------------- upstream fail

    #[Test]
    public function an_upstream_failure_is_shown_safely(): void
    {
        Http::fake(['*' => Http::response([
            'path' => 'https://stg7.esb.co.id/core-int/purchase/purchase-invoices',
            'timestamp' => '2026-03-26 10:30:00',
            'status' => 'fail',
            'code' => 'EC03100032',
            'message' => 'strconv.ParseInt: parsing "x": invalid syntax',
            'result' => null,
            'errors' => null,
        ], 500)]);

        $response = $this->actingAs($this->operator())->get(route('purchase-invoices.index'));

        $response->assertStatus(502);
        $response->assertDontSee('strconv');
    }

    #[Test]
    public function a_timeout_is_reported_as_a_gateway_timeout(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));

        $this->actingAs($this->operator())
            ->get(route('purchase-invoices.index'))
            ->assertStatus(504);
    }

    // ----------------------------------------------------------- observability

    #[Test]
    public function the_call_is_recorded_in_the_integration_log_against_the_user(): void
    {
        Http::fake(['*' => Http::response($this->listResponse())]);

        $operator = $this->operator();

        $this->actingAs($operator)->get(route('purchase-invoices.index'))->assertOk();

        $log = IntegrationLog::query()->sole();

        $this->assertSame('purchase-invoice.list', $log->operation);
        $this->assertSame('success', $log->outcome);
        $this->assertSame($operator->id, $log->user_id);
    }
}
