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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Importing purchase requests from an Excel file.
 *
 * POST /purchase/purchase-request/import takes a multipart `file` (xlsx or
 * xls, at most 2 MB) and answers with a plain envelope. The sheet layout is
 * the ERP's own and is not documented, so the gateway does not parse the
 * file: it checks that it is an Excel file of an acceptable size, forwards
 * it, and shows the ESB's per-row errors when it refuses.
 */
class PurchaseRequestImportTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://stg7.esb.co.id/core-int/purchase/purchase-request/import';

    /** The first bytes of every xlsx: it is a zip archive. */
    private const XLSX_MAGIC = "PK\x03\x04";

    /** The first bytes of every legacy xls: an OLE2 compound document. */
    private const XLS_MAGIC = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";

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

    private function manager(): User
    {
        return $this->userWithRole(Role::SuperAdmin);
    }

    private function xlsx(string $name = 'requests.xlsx', int $padBytes = 1024): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, self::XLSX_MAGIC.str_repeat('x', $padBytes));
    }

    /**
     * @return array<string, mixed>
     */
    private function envelope(string $code = 'EC03100000', string $message = 'OK', mixed $errors = null): array
    {
        return [
            'path' => self::URL,
            'timestamp' => '2026-03-26 10:30:00',
            'status' => $code === 'EC03100000' ? 'ok' : 'fail',
            'code' => $code,
            'message' => $message,
            'result' => null,
            'errors' => $errors,
        ];
    }

    // ---------------------------------------------------------------- access

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('purchase-requests.import.create'))->assertRedirect(route('login'));
        $this->post(route('purchase-requests.import.store'), ['file' => $this->xlsx()])->assertRedirect(route('login'));
    }

    #[Test]
    public function reading_erp_data_is_not_enough_to_import(): void
    {
        Http::fake();

        $operator = $this->userWithRole(Role::IntegrationOperator);

        $this->actingAs($operator)->get(route('purchase-requests.import.create'))->assertForbidden();
        $this->actingAs($operator)
            ->post(route('purchase-requests.import.store'), ['file' => $this->xlsx()])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function a_manager_sees_the_import_screen_without_calling_the_esb(): void
    {
        Http::fake();

        $this->actingAs($this->manager())
            ->get(route('purchase-requests.import.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('purchase-requests/import')
                ->where('maxKilobytes', 2048));

        Http::assertNothingSent();
    }

    // --------------------------------------------------------------- success

    #[Test]
    public function an_excel_file_is_forwarded_to_the_esb(): void
    {
        Http::fake(['*' => Http::response($this->envelope())]);

        $manager = $this->manager();
        $file = $this->xlsx('restock week 12.xlsx');
        $contents = (string) file_get_contents($file->getPathname());

        $this->actingAs($manager)
            ->post(route('purchase-requests.import.store'), ['file' => $file])
            ->assertRedirect(route('purchase-requests.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === self::URL
            && $request->isMultipart()
            && $request->hasFile('file', $contents, 'restock week 12.xlsx'));

        $audit = AuditLog::query()->sole();
        $this->assertSame('purchase-request.imported', $audit->action);
        $this->assertSame($manager->id, $audit->actor_id);
        $this->assertSame('restock week 12.xlsx', $audit->metadata['filename']);
        $this->assertSame(strlen($contents), $audit->metadata['bytes']);
        // What was sent can be matched to a file someone still has.
        $this->assertSame(hash('sha256', $contents), $audit->metadata['sha256']);

        $this->assertSame('purchase-request.import', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function a_legacy_xls_file_is_accepted_too(): void
    {
        Http::fake(['*' => Http::response($this->envelope())]);

        $file = UploadedFile::fake()->createWithContent('old.xls', self::XLS_MAGIC.str_repeat('x', 512));

        $this->actingAs($this->manager())
            ->post(route('purchase-requests.import.store'), ['file' => $file])
            ->assertSessionHasNoErrors();

        Http::assertSentCount(1);
    }

    #[Test]
    public function the_forwarded_filename_is_reduced_to_a_safe_basename(): void
    {
        Http::fake(['*' => Http::response($this->envelope())]);

        $file = UploadedFile::fake()->createWithContent("../../etc/pa\"ss\r\nwd.xlsx", self::XLSX_MAGIC.'x');

        $this->actingAs($this->manager())
            ->post(route('purchase-requests.import.store'), ['file' => $file]);

        Http::assertSent(fn (Request $request): bool => $request->hasFile('file', null, 'passwd.xlsx'));
    }

    // ------------------------------------------------------------ validation

    #[Test]
    public function a_file_is_required(): void
    {
        Http::fake();

        $this->actingAs($this->manager())
            ->post(route('purchase-requests.import.store'), [])
            ->assertSessionHasErrors('file');

        Http::assertNothingSent();
    }

    #[Test]
    public function a_file_that_is_not_excel_is_refused_even_when_named_like_one(): void
    {
        Http::fake();

        $manager = $this->manager();

        $this->actingAs($manager)
            ->post(route('purchase-requests.import.store'), [
                'file' => UploadedFile::fake()->createWithContent('requests.csv', "a,b\n1,2"),
            ])
            ->assertSessionHasErrors('file');

        // The name is only a claim; the content is what the ESB will parse.
        $this->actingAs($manager)
            ->post(route('purchase-requests.import.store'), [
                'file' => UploadedFile::fake()->createWithContent('requests.xlsx', '%PDF-1.7 not a sheet'),
            ])
            ->assertSessionHasErrors('file');

        Http::assertNothingSent();
    }

    #[Test]
    public function a_file_over_the_esbs_limit_is_refused_before_upload(): void
    {
        Http::fake();

        $this->actingAs($this->manager())
            ->post(route('purchase-requests.import.store'), ['file' => $this->xlsx(padBytes: 2 * 1024 * 1024)])
            ->assertSessionHasErrors('file');

        Http::assertNothingSent();
    }

    // --------------------------------------------------------------- refusal

    #[Test]
    public function the_esbs_row_errors_are_shown_on_the_import_screen(): void
    {
        Http::fake(['*' => Http::response($this->envelope('EC03100003', 'Validation Error', [
            ['attribute' => 'branchCode', 'field' => 'branchCode', 'code' => 'EC03100003', 'message' => 'branch SNX not found', 'row' => 3],
            ['attribute' => 'qty', 'field' => 'qty', 'code' => 'EC03100003', 'message' => 'qty must be greater than 0', 'row' => 7],
        ]), 400)]);

        $this->actingAs($this->manager())
            ->post(route('purchase-requests.import.store'), ['file' => $this->xlsx()])
            ->assertRedirect(route('purchase-requests.import.create'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'error')
            ->assertSessionHas('inertia.flash_data.importErrors', [
                ['row' => 3, 'field' => 'branchCode', 'message' => 'branch SNX not found'],
                ['row' => 7, 'field' => 'qty', 'message' => 'qty must be greater than 0'],
            ]);

        Http::assertSentCount(1);
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function a_timed_out_import_is_not_retried_and_is_reported_as_unconfirmed(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts): never {
            $attempts++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->actingAs($this->manager())
            ->post(route('purchase-requests.import.store'), ['file' => $this->xlsx()])
            ->assertRedirect(route('purchase-requests.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        // Importing again might create every request twice.
        $this->assertSame(1, $attempts);

        $audit = AuditLog::query()->sole();
        $this->assertSame('purchase-request.write-unconfirmed', $audit->action);
        $this->assertSame('import', $audit->metadata['operation']);
        $this->assertSame('requests.xlsx', $audit->metadata['filename']);
        $this->assertNotEmpty($audit->metadata['correlation_id']);
    }

    #[Test]
    public function a_second_import_from_the_same_person_while_one_is_in_flight_is_refused(): void
    {
        Http::fake();

        $manager = $this->manager();
        $lock = Cache::lock('purchase-request-import:'.$manager->id, 60);
        $lock->get();

        try {
            $this->actingAs($manager)
                ->post(route('purchase-requests.import.store'), ['file' => $this->xlsx()])
                ->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        Http::assertNothingSent();
    }
}
