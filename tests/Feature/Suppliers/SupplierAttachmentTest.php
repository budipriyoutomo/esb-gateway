<?php

namespace Tests\Feature\Suppliers;

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
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreSupplier;
use Tests\TestCase;

/**
 * Supplier attachments ([ESB-032], S4): PATCH /supplier/{id}/attachment
 * uploads files, DELETE removes all of them. suppliers.manage.
 */
class SupplierAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = 'https://stg7.esb.co.id/core-int';

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
        $user->assignRole(Role::SuperAdmin->value);

        return $user;
    }

    private function pdf(string $name = 'npwp.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.7\n rest");
    }

    /**
     * @return array<string, mixed>
     */
    private function ok(mixed $result = null): array
    {
        return ['status' => 'ok', 'code' => 'EC03100000', 'message' => 'OK', 'result' => $result, 'errors' => null];
    }

    #[Test]
    public function the_detail_shows_how_many_attachments_there_are_without_their_urls(): void
    {
        Http::fake(['*' => Http::response(CoreSupplier::detailResponse())]);
        $user = User::factory()->create();
        $user->assignRole(Role::IntegrationOperator->value);

        $this->actingAs($user)
            ->get(route('suppliers.show', 7))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('supplier.attachmentCount', 1)
                ->missing('supplier.imageAttachments'));
    }

    #[Test]
    public function attaching_and_removing_need_suppliers_manage(): void
    {
        Http::fake();
        $operator = User::factory()->create();
        $operator->assignRole(Role::IntegrationOperator->value);
        $this->actingAs($operator);

        $this->post(route('suppliers.attachments.store', 7), ['files' => [$this->pdf()]])->assertForbidden();
        $this->delete(route('suppliers.attachments.destroy', 7))->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function files_are_sent_once_under_one_field_and_audited(): void
    {
        $sent = [];
        Http::fake(function (Request $request) use (&$sent) {
            $sent[] = $request->method().' '.$request->url();
            $this->assertTrue($request->hasFile('files', "%PDF-1.7\n rest", 'npwp.pdf'));
            $this->assertTrue($request->hasFile('files', "\xFF\xD8\xFF\xE0 jpeg", 'siup.jpg'));

            return Http::response($this->ok(['urls' => ['https://cdn.example.test/a.pdf']]));
        });

        $this->actingAs($this->manager())
            ->post(route('suppliers.attachments.store', 7), ['name' => 'CV Maju Jaya', 'files' => [
                $this->pdf(),
                UploadedFile::fake()->createWithContent('siup.jpg', "\xFF\xD8\xFF\xE0 jpeg"),
            ]])
            ->assertRedirect(route('suppliers.show', 7))
            ->assertSessionHas('inertia.flash_data.toast', ['type' => 'success', 'message' => '2 files were attached to CV Maju Jaya.']);

        $this->assertSame(['PATCH '.self::BASE_URL.'/supplier/7/attachment'], $sent);
        $audit = AuditLog::query()->sole();
        $this->assertSame('supplier.attachments-uploaded', $audit->action);
        $this->assertSame(7, $audit->metadata['id']);
        $this->assertSame(['npwp.pdf', 'siup.jpg'], array_column($audit->metadata['files'], 'filename'));
        $this->assertSame(hash('sha256', "%PDF-1.7\n rest"), $audit->metadata['files'][0]['sha256']);
    }

    #[Test]
    public function removing_deletes_every_attachment_once(): void
    {
        $sent = [];
        Http::fake(function (Request $request) use (&$sent) {
            $sent[] = $request->method().' '.$request->url();

            return Http::response($this->ok());
        });

        $this->actingAs($this->manager())
            ->delete(route('suppliers.attachments.destroy', 7), ['name' => 'CV Maju Jaya'])
            ->assertRedirect(route('suppliers.show', 7))
            ->assertSessionHas('inertia.flash_data.toast.message', 'Every attachment of CV Maju Jaya was removed.');

        $this->assertSame(['DELETE '.self::BASE_URL.'/supplier/7/attachment'], $sent);
        $this->assertSame('supplier.attachments-deleted', AuditLog::query()->sole()->action);
    }

    #[Test]
    public function only_a_few_documents_or_images_are_accepted(): void
    {
        Http::fake();
        $this->actingAs($this->manager());

        foreach ([
            [],
            [UploadedFile::fake()->createWithContent('script.php', '<?php echo 1;')],
            [UploadedFile::fake()->create('huge.pdf', 5121, 'application/pdf')],
            array_fill(0, 6, $this->pdf()),
        ] as $files) {
            $this->from(route('suppliers.show', 7))
                ->post(route('suppliers.attachments.store', 7), ['files' => $files])
                ->assertSessionHasErrors();
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function a_timed_out_upload_is_not_retried_and_is_audited_as_unconfirmed(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts): never {
            $attempts++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->actingAs($this->manager())
            ->post(route('suppliers.attachments.store', 7), ['files' => [$this->pdf()]])
            ->assertRedirect(route('suppliers.show', 7))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $attempts);
        $audit = AuditLog::query()->sole();
        $this->assertSame('supplier.write-unconfirmed', $audit->action);
        $this->assertSame('attach', $audit->metadata['operation']);
    }

    #[Test]
    public function it_shares_the_suppliers_write_lock(): void
    {
        Http::fake();
        $lock = Cache::lock('supplier-write:7', 60);
        $lock->get();

        try {
            $this->actingAs($this->manager())
                ->delete(route('suppliers.attachments.destroy', 7))
                ->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        Http::assertNothingSent();
    }
}
