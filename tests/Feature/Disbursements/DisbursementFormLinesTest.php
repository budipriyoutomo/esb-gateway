<?php

namespace Tests\Feature\Disbursements;

use App\Authorization\Role;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreDisbursement;
use Tests\TestCase;

/**
 * Filling a disbursement form's lines from the ERP's Excel form ([ESB-040]):
 * POST /finance/disbursement/import/form parses the sheet and saves nothing,
 * so its rows only come back to the form, flashed for the page to pick up.
 * Neither locked nor audited; esb.invoke + disbursements.manage like the
 * form it fills.
 */
class DisbursementFormLinesTest extends TestCase
{
    use RefreshDatabase;

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

    private function manager(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::IntegrationOperator->value);
        $user->givePermissionTo('disbursements.manage');

        return $user;
    }

    private function read(?UploadedFile $file = null, ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->manager())
            ->from(route('disbursements.create'))
            ->post(route('disbursements.form-lines'), ['file' => $file ?? UploadedFile::fake()->createWithContent('form.xlsx', self::XLSX)]);
    }

    #[Test]
    public function it_needs_esb_invoke_and_the_manage_permission(): void
    {
        Http::fake();

        $operator = User::factory()->create();
        $operator->assignRole(Role::IntegrationOperator->value);
        $operator->givePermissionTo('disbursements.approve');

        $this->read(user: $operator)->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function the_parsed_rows_come_back_to_the_form(): void
    {
        Http::fake(['*' => Http::response(CoreDisbursement::formLines())]);

        $this->read()
            ->assertRedirect(route('disbursements.create'))
            ->assertSessionHas('inertia.flash_data.disbursementLines', [[
                'branchId' => 3,
                'branchName' => 'Outlet Senopati',
                'coaNo' => '6102',
                'coaDescription' => 'Beban Listrik',
                'description' => 'Listrik Maret',
                'amount' => 250000.0,
            ]])
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://stg7.esb.co.id/core-int/finance/disbursement/import/form'
            && $request->hasFile('file', self::XLSX, 'form.xlsx'));
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function a_file_that_is_not_a_workbook_never_reaches_the_esb(): void
    {
        Http::fake();

        $this->read(UploadedFile::fake()->createWithContent('form.csv', 'a,b'))->assertSessionHasErrors('file');
        $this->read(UploadedFile::fake()->createWithContent('form.xlsx', 'not a zip'))->assertSessionHasErrors('file');

        Http::assertNothingSent();
    }

    #[Test]
    public function the_erps_refusal_is_reported_on_the_form(): void
    {
        Http::fake(['*' => Http::response([
            ...CoreDisbursement::writeEnvelope('EC03100003', 'Validation Error', 'fail'),
            'errors' => [['attribute' => 'coaNo', 'code' => 'EC03100003', 'message' => 'COA 9999 does not exist', 'row' => 2]],
        ], 400)]);

        $this->read()
            ->assertRedirect(route('disbursements.create'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'error')
            ->assertSessionMissing('inertia.flash_data.disbursementLines');

        Http::assertSentCount(1);
    }
}
