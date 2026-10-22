<?php

namespace Tests\Feature\Inventory;

use App\Authorization\Role;
use App\Domain\Canonical\InventoryKind;
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
use Tests\Fixtures\CoreInventory;
use Tests\TestCase;

/**
 * Attachments of one inventory document (TODO-modules.md §3, S4,
 * [ESB-036]): PATCH {path}/{number}/attachment with the files under one
 * field, DELETE removes all of them where the vendor has one. esb.invoke +
 * <kind>.manage, one attempt, the document's lock, an audit row with each
 * file's name, size and SHA-256.
 *
 * Where the vendor's upload keeps only the "existing" URLs it is sent, an
 * upload would drop the current attachments; the gateway does not hold
 * those URLs, so it attaches only while there are none.
 */
abstract class InventoryAttachmentTestCase extends TestCase
{
    use RefreshDatabase;

    abstract protected function kind(): InventoryKind;

    abstract protected function upstreamPath(): string;

    abstract protected function number(): string;

    abstract protected function auditPrefix(): string;

    /**
     * The detail with this many attachments.
     *
     * @return array<string, mixed>
     */
    abstract protected function detailWithAttachments(int $count): array;

    /** Whether the vendor's upload replaces what it is not sent back. */
    abstract protected function replacesExisting(): bool;

    /** Whether the vendor can remove the attachments. */
    abstract protected function removable(): bool;

    /** A file of a type the vendor takes for this document. */
    abstract protected function acceptedFile(): UploadedFile;

    /** A file of a type it does not. */
    abstract protected function rejectedFile(): UploadedFile;

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

    protected function userWithRole(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    protected function manager(): User
    {
        $user = $this->userWithRole(Role::IntegrationOperator);
        $user->givePermissionTo($this->kind()->value.'.manage');

        return $user;
    }

    protected function attachmentUrl(): string
    {
        return 'https://stg7.esb.co.id/core-int'.$this->upstreamPath().'/'.$this->number().'/attachment';
    }

    /**
     * @param  list<UploadedFile>|null  $files
     */
    protected function upload(?array $files = null, ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->manager())
            ->post(route($this->kind()->value.'.attachments.store', $this->number()), ['files' => $files ?? [$this->acceptedFile()]]);
    }

    /**
     * Answer GETs with the detail (and its extra reads), writes with $write.
     *
     * @param  list<string>  $sent  "METHOD URL" of every request.
     */
    protected function fakeUpstream(array &$sent, int $attachments = 0, ?array $write = null): void
    {
        Http::fake(function (Request $request) use (&$sent, $attachments, $write) {
            $sent[] = $request->method().' '.$request->url();

            if ($request->method() !== 'GET') {
                return Http::response($write ?? CoreInventory::writeEnvelope());
            }

            if (str_contains($request->url(), '/stock-check/')) {
                return Http::response(CoreInventory::stockCheck(false, '/stock-check'));
            }

            return Http::response($this->detailWithAttachments($attachments));
        });
    }

    protected function showRoute(): string
    {
        return route($this->kind()->value.'.show', $this->number());
    }

    #[Test]
    public function attachments_need_esb_invoke_and_the_manage_permission(): void
    {
        Http::fake();

        $approver = $this->userWithRole(Role::IntegrationOperator);
        $approver->givePermissionTo($this->kind()->value.'.approve');
        $withoutInvoke = $this->userWithRole(Role::Viewer);
        $withoutInvoke->givePermissionTo($this->kind()->value.'.manage');

        foreach ([$this->userWithRole(Role::IntegrationOperator), $approver, $withoutInvoke] as $user) {
            $this->upload(user: $user)->assertForbidden();

            if ($this->removable()) {
                $this->actingAs($user)->delete(route($this->kind()->value.'.attachments.destroy', $this->number()))->assertForbidden();
            }
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function files_are_sent_once_under_one_field_and_audited(): void
    {
        $sent = [];
        $this->fakeUpstream($sent);
        $file = $this->acceptedFile();
        $contents = (string) file_get_contents($file->getRealPath());
        $manager = $this->manager();

        $this->upload([$file], $manager)
            ->assertSessionHasNoErrors()
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        $writes = array_values(array_filter($sent, static fn (string $line): bool => ! str_starts_with($line, 'GET ')));
        $this->assertSame(['PATCH '.$this->attachmentUrl()], $writes);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PATCH'
            && $request->hasFile('files', $contents, $file->getClientOriginalName()));

        $audit = AuditLog::query()->sole();
        $this->assertSame($this->auditPrefix().'.attachments-uploaded', $audit->action);
        $this->assertSame($manager->id, $audit->actor_id);
        $this->assertSame($this->number(), $audit->metadata['number']);
        $this->assertSame([$file->getClientOriginalName()], array_column($audit->metadata['files'], 'filename'));
        $this->assertSame(hash('sha256', $contents), $audit->metadata['files'][0]['sha256']);
    }

    #[Test]
    public function an_upload_that_would_drop_the_current_attachments_is_not_sent(): void
    {
        $sent = [];
        $this->fakeUpstream($sent, attachments: 2);

        $response = $this->upload();

        if (! $this->replacesExisting()) {
            // The vendor's upload adds to what is there.
            $response->assertSessionHas('inertia.flash_data.toast.type', 'success');
            $this->assertContains('PATCH '.$this->attachmentUrl(), $sent);

            return;
        }

        $response->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertNotContains('PATCH '.$this->attachmentUrl(), $sent);
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function a_file_of_another_type_never_reaches_the_esb(): void
    {
        Http::fake();

        $this->upload([$this->rejectedFile()])->assertSessionHasErrors('files.0');
        $this->upload([])->assertSessionHasErrors('files');

        Http::assertNothingSent();
    }

    #[Test]
    public function an_unconfirmed_upload_is_audited_once(): void
    {
        $writes = 0;
        Http::fake(function (Request $request) use (&$writes) {
            if ($request->method() === 'GET') {
                return str_contains($request->url(), '/stock-check/')
                    ? Http::response(CoreInventory::stockCheck(false, '/stock-check'))
                    : Http::response($this->detailWithAttachments(0));
            }

            $writes++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->upload()
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.unconfirmedWrite.reference');

        $this->assertSame(1, $writes);

        $audit = AuditLog::query()->sole();
        $this->assertSame($this->auditPrefix().'.write-unconfirmed', $audit->action);
        $this->assertSame('attach', $audit->metadata['operation']);
    }

    #[Test]
    public function an_upload_shares_the_documents_write_lock(): void
    {
        $sent = [];
        $this->fakeUpstream($sent);

        $lock = Cache::lock($this->auditPrefix().'-write:'.$this->number(), 60);
        $lock->get();

        try {
            $this->upload()->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        $this->assertNotContains('PATCH '.$this->attachmentUrl(), $sent);
    }

    #[Test]
    public function removing_deletes_every_attachment_once_where_the_vendor_can(): void
    {
        if (! $this->removable()) {
            $this->assertFalse(Route::has($this->kind()->value.'.attachments.destroy'));

            return;
        }

        $sent = [];
        $this->fakeUpstream($sent);

        $this->actingAs($this->manager())
            ->delete(route($this->kind()->value.'.attachments.destroy', $this->number()))
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        $this->assertSame(['DELETE '.$this->attachmentUrl()], $sent);
        $this->assertSame($this->auditPrefix().'.attachments-deleted', AuditLog::query()->sole()->action);
    }
}
