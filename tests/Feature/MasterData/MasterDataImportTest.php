<?php

namespace Tests\Feature\MasterData;

use App\Authorization\Role;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Handing the ERP a spreadsheet of master data ([ESB-032], S4). The gateway
 * neither parses nor stores the file; most imports are queued by the ERP.
 */
class MasterDataImportTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = 'https://stg7.esb.co.id/core-int';

    private const XLSX = "PK\x03\x04 rest of a workbook";

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
     * kind => [HTTP verb, upstream path, the permission it needs, where it lands]
     *
     * @return iterable<string, array{string, string, string, string, string}>
     */
    public static function kinds(): iterable
    {
        yield 'units' => ['units', 'POST', '/units/import', 'units.manage', 'units.index'];
        yield 'product categories' => ['product-categories', 'POST', '/product/category/upload', 'product-categories.manage', 'product-categories.index'];
        yield 'product sub-categories' => ['product-sub-categories', 'POST', '/product/sub-category/upload', 'product-sub-categories.manage', 'product-sub-categories.index'];
        yield 'new products' => ['products-create', 'POST', '/product/import', 'products.manage', 'products.index'];
        yield 'product changes' => ['products-update', 'PUT', '/product/import', 'products.manage', 'products.index'];
        yield 'pricelists' => ['pricelists', 'POST', '/pricelist/import', 'pricelists.manage', 'pricelists.index'];
        yield 'new suppliers' => ['suppliers-create', 'POST', '/supplier/upload', 'suppliers.manage', 'suppliers.index'];
        yield 'supplier changes' => ['suppliers-update', 'PATCH', '/supplier/upload', 'suppliers.manage', 'suppliers.index'];
        yield 'COA accounts' => ['coa-accounts', 'POST', '/accounting/coa/upload', 'coa.manage', 'coa-accounts.index'];
        // Not master data, but the same queued spreadsheet import ([ESB-036]).
        yield 'simple transfers' => ['simple-transfers', 'POST', '/simple-transfer/import', 'simple-transfers.manage', 'simple-transfers.index'];
        // Queued as well ([ESB-040]).
        yield 'disbursements' => ['disbursements', 'POST', '/finance/disbursement/import', 'disbursements.manage', 'disbursements.index'];
    }

    private function holder(string $permission): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::IntegrationOperator->value);
        $user->givePermissionTo(Permission::findByName($permission));

        return $user;
    }

    private function workbook(string $name = 'sheet.xlsx', string $contents = self::XLSX): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $contents);
    }

    /**
     * @return array<string, mixed>
     */
    private function ok(mixed $result = null): array
    {
        return ['status' => 'ok', 'code' => 'EC03100000', 'message' => 'OK', 'result' => $result, 'errors' => null];
    }

    #[Test]
    #[DataProvider('kinds')]
    public function it_needs_the_modules_own_manage_permission(string $kind, string $verb, string $path, string $permission): void
    {
        Http::fake();
        $operator = User::factory()->create();
        $operator->assignRole(Role::IntegrationOperator->value);
        $this->actingAs($operator);

        $this->get(route('imports.create', $kind))->assertForbidden();
        $this->post(route('imports.store', $kind), ['file' => $this->workbook()])->assertForbidden();

        // Another module's permission is not enough either.
        $other = $permission === 'units.manage' ? 'coa.manage' : 'units.manage';
        $this->actingAs($this->holder($other))
            ->post(route('imports.store', $kind), ['file' => $this->workbook()])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    #[DataProvider('kinds')]
    public function the_file_is_sent_once_with_the_vendors_verb_and_audited(string $kind, string $verb, string $path, string $permission, string $landing): void
    {
        $sent = [];
        Http::fake(function (Request $request) use (&$sent) {
            $sent[] = $request->method().' '.$request->url();
            $this->assertTrue($request->hasFile('file', self::XLSX, 'my sheet.xlsx'));

            return Http::response($this->ok());
        });
        $user = $this->holder($permission);

        $this->actingAs($user)->get(route('imports.create', $kind))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('imports/create')->where('import.kind', $kind));

        $this->post(route('imports.store', $kind), ['file' => $this->workbook('my sheet.xlsx')])
            ->assertRedirect(route($landing))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        $this->assertSame([$verb.' '.self::BASE_URL.$path], $sent);
        $audit = AuditLog::query()->sole();
        $this->assertSame('master-data.imported', $audit->action);
        $this->assertSame($kind, $audit->metadata['kind']);
        $this->assertSame('my sheet.xlsx', $audit->metadata['filename']);
        $this->assertSame(hash('sha256', self::XLSX), $audit->metadata['sha256']);
    }

    #[Test]
    public function a_queued_import_says_the_erp_imports_it_later(): void
    {
        Http::fake(['*' => Http::response($this->ok())]);

        $this->actingAs($this->holder('units.manage'))
            ->post(route('imports.store', 'units'), ['file' => $this->workbook('units.xlsx')])
            ->assertSessionHas(
                'inertia.flash_data.toast.message',
                'units.xlsx was handed to the ERP, which imports it in the background. Check the list in a few minutes: rows the ERP rejects later are not reported here.',
            );
    }

    #[Test]
    public function a_coa_import_reports_what_the_erp_saved(): void
    {
        Http::fake(['*' => Http::response($this->ok(['coaCount' => 12, 'accountMappingCount' => 3]))]);

        $this->actingAs($this->holder('coa.manage'))
            ->post(route('imports.store', 'coa-accounts'), ['file' => $this->workbook('coa.xlsx')])
            ->assertSessionHas('inertia.flash_data.toast.message', 'coa.xlsx was imported: 12 accounts and 3 account mappings.');

        $this->assertSame(['accounts' => 12, 'mappings' => 3], AuditLog::query()->sole()->metadata['saved']);
    }

    #[Test]
    public function a_refused_sheet_comes_back_with_the_erps_row_errors(): void
    {
        Http::fake(['*' => Http::response([
            'status' => 'fail', 'code' => 'EC03100003', 'message' => 'Validation Error', 'result' => null,
            'errors' => [['row' => 3, 'attribute' => 'uomName', 'code' => 'EC03100003', 'message' => 'Unit name already used']],
        ], 400)]);

        $this->actingAs($this->holder('units.manage'))
            ->post(route('imports.store', 'units'), ['file' => $this->workbook()])
            ->assertRedirect(route('imports.create', 'units'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'error')
            ->assertSessionHas('inertia.flash_data.importErrors', [['field' => 'uomName', 'row' => 3, 'message' => 'Unit name already used']]);

        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function a_timed_out_import_is_not_retried_and_is_audited_as_unconfirmed(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts): never {
            $attempts++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->actingAs($this->holder('products.manage'))
            ->post(route('imports.store', 'products-create'), ['file' => $this->workbook()])
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $attempts);
        $audit = AuditLog::query()->sole();
        $this->assertSame('master-data.import-unconfirmed', $audit->action);
        $this->assertSame('products-create', $audit->metadata['kind']);
        $this->assertNotEmpty($audit->metadata['correlation_id']);
    }

    #[Test]
    public function a_second_import_of_the_same_kind_while_one_is_in_flight_is_refused(): void
    {
        Http::fake();
        $user = $this->holder('units.manage');
        $lock = Cache::lock('master-data-import:units:'.$user->id, 60);
        $lock->get();

        try {
            $this->actingAs($user)
                ->post(route('imports.store', 'units'), ['file' => $this->workbook()])
                ->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function a_file_that_is_not_a_workbook_never_reaches_the_esb(): void
    {
        Http::fake();
        $this->actingAs($this->holder('products.manage'));

        foreach ([
            ['units', $this->workbook('units.xls', "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1")],  // the vendor takes .xlsx only here
            ['products-create', $this->workbook('fake.xlsx', 'not a zip')],
            ['products-create', UploadedFile::fake()->create('big.xlsx', 2049)],
            ['products-create', $this->workbook('sheet.csv', 'a,b')],
        ] as [$kind, $file]) {
            $user = $kind === 'units' ? $this->holder('units.manage') : $this->holder('products.manage');
            $this->actingAs($user)
                ->from(route('imports.create', $kind))
                ->post(route('imports.store', $kind), ['file' => $file])
                ->assertSessionHasErrors('file');
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function an_unknown_kind_is_a_404(): void
    {
        $this->actingAs($this->holder('units.manage'))->get('/imports/everything')->assertNotFound();
    }
}
