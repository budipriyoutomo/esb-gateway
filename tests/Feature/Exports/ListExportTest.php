<?php

namespace Tests\Feature\Exports;

use App\Authorization\Role;
use App\Exports\CsvExport;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreAccounting;
use Tests\Fixtures\CoreCoaAccount;
use Tests\Fixtures\CoreDisbursement;
use Tests\Fixtures\CorePricelist;
use Tests\Fixtures\CoreProductCategory;
use Tests\Fixtures\CoreProductSubCategory;
use Tests\Fixtures\CoreSupplier;
use Tests\Fixtures\CoreUnitOfMeasure;
use Tests\TestCase;

/**
 * List exports: CSV built by the gateway from the list's own reads
 * (ADR-031, business decision 2026-10-22).
 */
class ListExportTest extends TestCase
{
    use RefreshDatabase;

    private const BOM = "\u{FEFF}";

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

    private function operator(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::IntegrationOperator->value);

        return $user;
    }

    /**
     * @return list<string>
     */
    private function lines(TestResponse $response): array
    {
        $body = (string) $response->getContent();
        $this->assertStringStartsWith(self::BOM, $body);

        return explode("\r\n", rtrim(substr($body, strlen(self::BOM)), "\r\n"));
    }

    // ================================================================= access

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('units.export'))->assertRedirect(route('login'));
    }

    #[Test]
    public function exporting_needs_what_reading_the_list_needs(): void
    {
        Http::fake();

        $viewer = User::factory()->create();
        $viewer->assignRole(Role::Viewer->value);

        $this->actingAs($viewer)->get(route('units.export'))->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function bad_filters_are_refused_before_reaching_the_esb(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('units.export', ['flagActive' => 2]))
            ->assertSessionHasErrors('flagActive');

        Http::assertNothingSent();
    }

    // ================================================================ the file

    #[Test]
    public function the_export_is_a_csv_of_every_row_with_the_lists_filters(): void
    {
        Http::fake(['*' => Http::response(CoreUnitOfMeasure::listResponse())]);

        $response = $this->actingAs($this->operator())
            ->get(route('units.export', ['search' => 'KG', 'flagActive' => 1, 'page' => 3, 'limit' => 10]))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $this->assertMatchesRegularExpression(
            '/^attachment; filename="units-\d{8}-\d{6}\.csv"$/',
            (string) $response->headers->get('Content-Disposition'),
        );
        $this->assertSame([
            'ID,Name,Metric,Tax code,Tax,Notes,Active',
            '4,KG,Weight,KGM,Kilogram,Kilogram,Yes',
        ], $this->lines($response));

        // From the first page, at the largest page size, whatever page the
        // list was on; the filters are the list's.
        Http::assertSentCount(1);
        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $query['page'] === '1'
                && $query['limit'] === (string) CsvExport::PAGE_SIZE
                && in_array('KG', $query, true);
        });

        $audit = AuditLog::query()->sole();
        $this->assertSame('list.exported', $audit->action);
        $this->assertSame('units', $audit->metadata['list']);
        $this->assertSame(1, $audit->metadata['rows']);
        $this->assertSame(['search' => 'KG', 'flagActive' => 1], $audit->metadata['filters']);
    }

    #[Test]
    public function every_page_is_read(): void
    {
        // One row per answer, two rows in all: two reads.
        Http::fake(['*' => Http::response(CoreUnitOfMeasure::listResponse(count: 2))]);

        $this->actingAs($this->operator())->get(route('units.export'))->assertOk();

        $pages = [];
        Http::assertSent(function (Request $request) use (&$pages): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $pages[] = $query['page'];

            return true;
        });
        $this->assertSame(['1', '2'], $pages);
    }

    #[Test]
    public function too_many_rows_are_refused_on_the_list(): void
    {
        Http::fake(['*' => Http::response(CoreUnitOfMeasure::listResponse(count: CsvExport::MAX_ROWS + 1))]);

        $this->actingAs($this->operator())
            ->from(route('units.index', ['search' => 'K']))
            ->get(route('units.export', ['search' => 'K']))
            ->assertRedirect(route('units.index', ['search' => 'K']))
            ->assertSessionHas('inertia.flash_data.toast.type', 'error');

        Http::assertSentCount(1);
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function a_failed_read_is_an_error_not_a_truncated_file(): void
    {
        Http::fake(function (): never {
            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $response = $this->actingAs($this->operator())->get(route('units.export'));

        $this->assertNotSame(200, $response->getStatusCode());
        $this->assertStringNotContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertSame(0, AuditLog::query()->count());
    }

    // ========================================================== general ledger

    #[Test]
    public function the_ledger_needs_an_account_and_a_period(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('general-ledger.export'))
            ->assertRedirect(route('general-ledger.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'error');

        Http::assertNothingSent();
    }

    #[Test]
    public function the_ledger_is_read_once_however_long_it_is(): void
    {
        $entries = array_fill(0, 250, CoreAccounting::ledgerEntry());
        Http::fake(['*' => Http::response(CoreAccounting::ledger($entries))]);

        $response = $this->actingAs($this->operator())
            ->get(route('general-ledger.export', ['coaNo' => '1101', 'dateFrom' => '2026-03-01', 'dateTo' => '2026-03-31']))
            ->assertOk();

        $lines = $this->lines($response);
        $this->assertSame('Date,Branch,Account no.,Account,Transaction type,Reference,Notes,Debit,Credit,Cost center,Project', $lines[0]);
        $this->assertCount(251, $lines);

        // The ERP answers the whole period at once; the gateway pages it.
        Http::assertSentCount(1);
    }

    // ============================================================ every list

    /**
     * @return array<string, array{string, array<string, mixed>, \Closure(): array<mixed>, string}>
     */
    public static function lists(): array
    {
        return [
            'product categories' => ['product-categories.export', [], fn () => CoreProductCategory::listResponse(), 'ID,Name,Type,Notes,Active'],
            'product sub-categories' => ['product-sub-categories.export', [], fn () => CoreProductSubCategory::listResponse(), 'ID,Name,Dead stock days,Notes,Active'],
            'products' => ['products.export', [], fn () => self::productListResponse(), 'ID,Code,Name,Category,Sub-category,Category type,Bill of material,Active'],
            'pricelists' => ['pricelists.export', [], fn () => CorePricelist::listResponse(), 'Pricelist no.,Product code,Product,Unit,Supplier,Currency,Price,Price date,Expires,Branches'],
            'chart of accounts' => ['coa-accounts.export', [], fn () => CoreCoaAccount::listResponse(), 'Account no.,Description,Level,Currency,Module account,Budget allowed,Active'],
            'suppliers' => ['suppliers.export', [], fn () => CoreSupplier::listResponse(), 'ID,Code,Name,Category,Address,Contact,Cell phone,Credit days,VAT subject,Active'],
            'disbursements' => ['disbursements.export', [], fn () => CoreDisbursement::listResponse([CoreDisbursement::row()]), 'Number,Date,Branch,Supplier,Payment,Reference,Grand total,Due date,Overdue,Status,Notes'],
            'general journals' => ['general-journals.export', [], fn () => CoreAccounting::journalList([CoreAccounting::journalRow()]), 'Journal ID,Date,Transaction type,Reference,Notes'],
            'memorial journals' => ['memorial-journals.export', [], fn () => CoreAccounting::mjList([CoreAccounting::mjRow()]), 'Number,Date,Status,Created by,Notes'],
            'ledger summary' => ['general-ledger.summary.export', ['dateFrom' => '2026-03-01', 'dateTo' => '2026-03-31'], fn () => CoreAccounting::summary([CoreAccounting::summaryRow()]), 'Date,Branch code,Branch,Account no.,Account,Debit,Credit'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  \Closure(): array<mixed>  $body
     */
    #[Test]
    #[DataProvider('lists')]
    public function each_list_exports_its_rows(string $route, array $filters, \Closure $body, string $header): void
    {
        Http::fake(['*' => Http::response($body())]);

        $lines = $this->lines($this->actingAs($this->operator())->get(route($route, $filters))->assertOk());

        $this->assertSame($header, $lines[0]);
        $this->assertGreaterThan(1, count($lines), 'at least one row');
        $this->assertSame('list.exported', AuditLog::query()->sole()->action);
    }

    /**
     * @return array<string, mixed>
     */
    private static function productListResponse(): array
    {
        return [
            'path' => 'https://stg7.esb.co.id/core-int/product/list',
            'timestamp' => '2024-05-28 18:20:51',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => [
                'page' => 1,
                'limit' => 100,
                'count' => 1,
                'data' => [[
                    'productID' => 75,
                    'productName' => 'Air mineral',
                    'productCode' => 'Air_mineral',
                    'categoryID' => 14,
                    'categoryName' => 'Kategori Test',
                    'subCategoryID' => 10,
                    'subCategoryName' => 'BUDGET INV',
                    'bomID' => null,
                    'bomName' => null,
                    'categoryTypeID' => 1,
                    'categoryTypeName' => 'Inventory',
                    'flagActive' => 1,
                ]],
                'prev' => '',
                'next' => '',
            ],
            'errors' => null,
        ];
    }
}
