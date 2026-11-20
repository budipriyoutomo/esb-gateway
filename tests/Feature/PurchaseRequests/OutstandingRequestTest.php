<?php

namespace Tests\Feature\PurchaseRequests;

use App\Actions\PurchaseRequests\BuildOutstandingRequestReport;
use App\Authorization\Role;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreInventory;
use Tests\Fixtures\CorePurchaseOrder;
use Tests\Fixtures\CoreRequestMonitoring;
use Tests\TestCase;

/**
 * The PR outstanding report ([ESB-053], ADR-034) end to end: route,
 * authorization, validation, both ESB reads, the page and the CSV.
 */
class OutstandingRequestTest extends TestCase
{
    use RefreshDatabase;

    private const PERIOD = ['requiredDateFrom' => '2026-03-01', 'requiredDateTo' => '2026-03-31', 'branchId' => 3];

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seed(RolePermissionSeeder::class);
        Date::setTestNow('2026-04-01 09:00:00');

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
     * Request monitoring answers one unordered item; the order lists answer
     * one Receiving order whose only line is part received on its one
     * authorized goods receipt. The order's detailQty is the conversion
     * factor, as on staging, and must not count as received.
     *
     * @param  int  $orders  how many open orders the lists report
     */
    private function fakeEsb(int $orders = 1): void
    {
        $detail = CorePurchaseOrder::detail(['statusID' => 4, 'statusName' => 'Receiving']);
        $detail['purchaseDetails'][0] = [...$detail['purchaseDetails'][0], 'qty' => 200, 'detailQty' => 1000, 'convertionQty' => 1000, 'requestQty' => 200];
        $receipt = CoreInventory::grDetail(['goodsReceiptNum' => 'GR202603250002', 'statusID' => 3, 'statusName' => 'Authorized']);
        $receipt['result']['goodsReceiptDetail'][0] = [...$receipt['result']['goodsReceiptDetail'][0], 'productDetailID' => 901, 'qty' => 120];

        Http::fake(function (Request $request) use ($detail, $receipt, $orders) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return match (true) {
                str_ends_with($path, '/purchase/request-monitoring') => Http::response(CoreRequestMonitoring::response()),
                str_ends_with($path, '/purchase/purchase-order') => Http::response(
                    ($query['statusID'] ?? null) === '4'
                        ? CorePurchaseOrder::listResponse([CorePurchaseOrder::row(['statusID' => 4])], count: $orders)
                        : CorePurchaseOrder::listResponse([], count: 0),
                ),
                str_contains($path, '/purchase/purchase-order/') => Http::response(CorePurchaseOrder::detailResponse($detail)),
                str_contains($path, '/inventory/goods-receipt/stock-check/') => Http::response(CoreInventory::stockCheck(false, $path)),
                str_ends_with($path, '/inventory/goods-receipt/GR202603250002') => Http::response($receipt),
                default => Http::response(['code' => 'EC03100004', 'message' => 'Not found'], 404),
            };
        });
    }

    /**
     * @return list<string>
     */
    private function csvLines(TestResponse $response): array
    {
        $body = (string) $response->getContent();

        return array_values(array_filter(explode("\r\n", ltrim($body, "\u{FEFF}")), static fn (string $line): bool => $line !== ''));
    }

    // ---------------------------------------------------------------- access

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('pr-outstanding.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function a_viewer_may_not_read_erp_data_through_the_gateway(): void
    {
        Http::fake();

        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('pr-outstanding.index', self::PERIOD))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    // ------------------------------------------------------------ the filters

    #[Test]
    public function nothing_is_read_until_a_required_date_period_is_given(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('pr-outstanding.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('purchase-requests/outstanding')
                ->where('report', null)
                ->where('error', null)
                ->has('stages', 4));

        Http::assertNothingSent();
    }

    #[Test]
    public function a_period_longer_than_the_cap_never_reaches_the_esb(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('pr-outstanding.index', ['requiredDateFrom' => '2026-01-01', 'requiredDateTo' => '2026-04-30']))
            ->assertSessionHasErrors('requiredDateTo');

        $this->actingAs($this->operator())
            ->get(route('pr-outstanding.index', [...self::PERIOD, 'stage' => 'lost']))
            ->assertSessionHasErrors('stage');

        Http::assertNothingSent();
    }

    // ------------------------------------------------------------- the report

    #[Test]
    public function the_report_joins_unordered_items_and_open_orders(): void
    {
        $this->fakeEsb();

        $this->actingAs($this->operator())
            ->get(route('pr-outstanding.index', [...self::PERIOD, 'purchaseRequestNumber' => CoreRequestMonitoring::PR]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('purchase-requests/outstanding')
                ->has('report.items', 2)
                ->where('report.items.0.stage', 'not-ordered')
                ->where('report.items.0.outstandingQty', 20)
                ->where('report.items.0.overdueDays', 7)
                ->where('report.items.1.stage', 'partially-received')
                ->where('report.items.1.purchaseOrderNumber', CorePurchaseOrder::NUMBER)
                ->where('report.items.1.outstandingQty', 80)
                ->where('report.stageCounts', ['not-ordered' => 1, 'not-received' => 0, 'partially-received' => 1, 'not-as-requested' => 0])
                ->where('report.pagination.count', 2)
                ->where('filters.requiredDateFrom', '2026-03-01'));

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && str_ends_with($request->url(), '/purchase/request-monitoring')
                && $request['branchIDs'] === [3]
                && $request['requiredDateFrom'] === '2026-03-01'
                && $request['requiredDateTo'] === '2026-03-31'
                && $request['purchaseRequestNum'] === CoreRequestMonitoring::PR;
        });

        $statuses = [];
        Http::assertSent(function (Request $request) use (&$statuses): bool {
            if ($request->method() !== 'GET' || ! str_contains($request->url(), '/purchase/purchase-order?')) {
                return true;
            }

            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $statuses[] = $query['statusID'];

            return $query['requiredDateFrom'] === '2026-03-01'
                && $query['branchIDs'] === '3'
                && $query['purchaseRequestNums'] === CoreRequestMonitoring::PR;
        });
        $this->assertSame(['3', '4'], $statuses);
    }

    #[Test]
    public function too_many_open_orders_are_refused_on_the_page(): void
    {
        $this->fakeEsb(orders: BuildOutstandingRequestReport::MAX_ORDERS + 1);

        $this->actingAs($this->operator())
            ->get(route('pr-outstanding.index', self::PERIOD))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('report', null)
                ->where('error', fn (string $error): bool => str_contains($error, 'Narrow')));

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/purchase/purchase-order/'));
    }

    // ---------------------------------------------------------------- the CSV

    #[Test]
    public function the_export_is_a_csv_of_every_row(): void
    {
        $this->fakeEsb();

        $response = $this->actingAs($this->operator())
            ->get(route('pr-outstanding.export', [...self::PERIOD, 'page' => 2, 'limit' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $lines = $this->csvLines($response);
        $this->assertCount(3, $lines);
        $this->assertStringStartsWith('Stage,PR,PR date,PO,PO date,Required,Branch,Supplier,Product code,Product,Unit,', $lines[0]);
        $this->assertStringStartsWith('Not ordered,'.CoreRequestMonitoring::PR.',', $lines[1]);
        $this->assertStringStartsWith('Partially received,', $lines[2]);

        $this->assertSame('pr-outstanding', AuditLog::query()->sole()->metadata['list']);
    }

    #[Test]
    public function the_export_needs_a_period(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('pr-outstanding.export'))
            ->assertRedirect(route('pr-outstanding.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'error');

        Http::assertNothingSent();
    }
}
