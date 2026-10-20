<?php

namespace Tests\Feature\Disbursements;

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
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreDisbursement;
use Tests\TestCase;

/**
 * Disbursement attachments ([ESB-040], S4): PATCH .../attachment with the
 * files under one field, esb.invoke + disbursements.manage, one attempt, the
 * disbursement's lock, an audit row with each file's name, size and SHA-256.
 * The upload keeps only the "existing" references it is sent, which the
 * gateway does not send: files are attached only while there are none. The
 * vendor has no delete.
 */
class DisbursementAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private const ATTACHMENT_URL = 'https://stg7.esb.co.id/core-int/finance/disbursement/'.CoreDisbursement::NUMBER.'/attachment';

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

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('nota sewa.pdf', "%PDF-1.7\n rest");
    }

    /**
     * @param  list<UploadedFile>|null  $files
     */
    private function upload(?array $files = null, ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->manager())
            ->post(route('disbursements.attachments.store', CoreDisbursement::NUMBER), ['files' => $files ?? [$this->pdf()]]);
    }

    /**
     * @param  list<string>  $sent  "METHOD URL" of every request.
     */
    private function fakeUpstream(array &$sent, int $attachments = 0): void
    {
        Http::fake(function (Request $request) use (&$sent, $attachments) {
            $sent[] = $request->method().' '.$request->url();

            return $request->method() === 'GET'
                ? Http::response(CoreDisbursement::detail(['imageAttachments' => array_map(
                    static fn (int $i): string => 'https://cdn.example.test/'.$i.'.pdf',
                    $attachments === 0 ? [] : range(1, $attachments),
                )]))
                : Http::response(CoreDisbursement::writeEnvelope(result: ['urls' => []]));
        });
    }

    #[Test]
    public function attachments_need_esb_invoke_and_the_manage_permission(): void
    {
        Http::fake();

        $approver = User::factory()->create();
        $approver->assignRole(Role::IntegrationOperator->value);
        $approver->givePermissionTo('disbursements.approve');
        $withoutInvoke = User::factory()->create();
        $withoutInvoke->assignRole(Role::Viewer->value);
        $withoutInvoke->givePermissionTo('disbursements.manage');

        $this->upload(user: $approver)->assertForbidden();
        $this->upload(user: $withoutInvoke)->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function files_are_sent_once_under_one_field_and_audited(): void
    {
        $sent = [];
        $this->fakeUpstream($sent);
        $file = $this->pdf();
        $manager = $this->manager();

        $this->upload([$file], $manager)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('disbursements.show', CoreDisbursement::NUMBER))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        $this->assertSame(['PATCH '.self::ATTACHMENT_URL], array_values(array_filter($sent, static fn (string $line): bool => ! str_starts_with($line, 'GET '))));
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PATCH'
            && $request->hasFile('files', "%PDF-1.7\n rest", 'nota sewa.pdf'));

        $audit = AuditLog::query()->sole();
        $this->assertSame('disbursement.attachments-uploaded', $audit->action);
        $this->assertSame($manager->id, $audit->actor_id);
        $this->assertSame(CoreDisbursement::NUMBER, $audit->metadata['number']);
        $this->assertSame(['nota sewa.pdf'], array_column($audit->metadata['files'], 'filename'));
        $this->assertSame(hash('sha256', "%PDF-1.7\n rest"), $audit->metadata['files'][0]['sha256']);
    }

    #[Test]
    public function an_upload_that_would_drop_the_current_attachments_is_not_sent(): void
    {
        $sent = [];
        $this->fakeUpstream($sent, attachments: 2);

        $this->upload()
            ->assertRedirect(route('disbursements.show', CoreDisbursement::NUMBER))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertNotContains('PATCH '.self::ATTACHMENT_URL, $sent);
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function a_file_of_another_type_or_too_many_never_reach_the_esb(): void
    {
        Http::fake();

        $this->upload([UploadedFile::fake()->createWithContent('nota.docx', "PK\x03\x04 rest")])->assertSessionHasErrors('files.0');
        $this->upload([UploadedFile::fake()->create('big.pdf', 2049, 'application/pdf')])->assertSessionHasErrors('files.0');
        $this->upload(array_map(fn (): UploadedFile => $this->pdf(), range(1, 6)))->assertSessionHasErrors('files');
        $this->upload([])->assertSessionHasErrors('files');

        Http::assertNothingSent();
    }

    #[Test]
    public function an_unconfirmed_upload_is_audited_once(): void
    {
        $writes = 0;
        Http::fake(function (Request $request) use (&$writes) {
            if ($request->method() === 'GET') {
                return Http::response(CoreDisbursement::detail());
            }

            $writes++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->upload()->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $writes);

        $audit = AuditLog::query()->sole();
        $this->assertSame('disbursement.write-unconfirmed', $audit->action);
        $this->assertSame('attach', $audit->metadata['operation']);
    }

    #[Test]
    public function an_upload_shares_the_disbursements_write_lock(): void
    {
        $sent = [];
        $this->fakeUpstream($sent);

        $lock = Cache::lock('disbursement-write:'.CoreDisbursement::NUMBER, 60);
        $lock->get();

        try {
            $this->upload()->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        $this->assertNotContains('PATCH '.self::ATTACHMENT_URL, $sent);
    }

    #[Test]
    public function the_vendor_has_no_way_to_remove_them(): void
    {
        $this->assertFalse(Route::has('disbursements.attachments.destroy'));
    }
}
