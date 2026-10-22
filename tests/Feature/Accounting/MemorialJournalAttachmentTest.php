<?php

namespace Tests\Feature\Accounting;

use App\Authorization\Role;
use App\Http\Controllers\Exports\ListExportController;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreAccounting;

/**
 * Asset images of a memorial journal ([ESB-046], S4): esb.invoke +
 * memorial-journals.manage, one image per call under the vendor's "file"
 * field, sent once, under the journal's lock, audited by name, size and
 * SHA-256. The vendor adds images; it takes no list of existing ones.
 */
class MemorialJournalAttachmentTest extends AccountingTestCase
{
    private const IMAGE_URL = 'https://stg7.esb.co.id/core-int/accounting/memorial-journal/'.CoreAccounting::MJ.'/asset-images';

    private function manager(): User
    {
        $user = $this->operator();
        $user->givePermissionTo('memorial-journals.manage');

        return $user;
    }

    private function jpg(): UploadedFile
    {
        return UploadedFile::fake()->image('bukti koreksi.jpg', 20, 20);
    }

    private function upload(?UploadedFile $file = null, ?User $user = null, string $number = CoreAccounting::MJ): TestResponse
    {
        return $this->actingAs($user ?? $this->manager())
            ->post(route('memorial-journals.images.store', $number), ['file' => $file ?? $this->jpg()]);
    }

    private function fakeOk(): void
    {
        Http::fake(['*' => Http::response(CoreAccounting::mjWrite(result: [
            'refNum' => CoreAccounting::MJ,
            'transactionName' => 'Memorial Journal',
            'assetImage' => [['ID' => 6, 'imageUrl' => 'https://cdn.example.test/bukti.jpg', 'refNum' => CoreAccounting::MJ]],
        ]))]);
    }

    #[Test]
    public function images_need_esb_invoke_and_the_manage_permission(): void
    {
        Http::fake();

        $approver = $this->operator();
        $approver->givePermissionTo('memorial-journals.approve');
        $withoutInvoke = User::factory()->create();
        $withoutInvoke->assignRole(Role::Viewer->value);
        $withoutInvoke->givePermissionTo('memorial-journals.manage');

        $this->upload(user: $approver)->assertForbidden();
        $this->upload(user: $withoutInvoke)->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function an_image_is_sent_once_under_the_vendors_field_and_audited(): void
    {
        $this->fakeOk();
        $file = $this->jpg();
        $contents = (string) file_get_contents($file->getPathname());
        $manager = $this->manager();

        $this->upload($file, $manager)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('memorial-journals.show', CoreAccounting::MJ))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        // Added, not replaced: nothing is read first.
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === self::IMAGE_URL
            && $request->hasFile('file', $contents, 'bukti koreksi.jpg'));

        $audit = AuditLog::query()->sole();
        $this->assertSame('memorial-journal.image-uploaded', $audit->action);
        $this->assertSame($manager->id, $audit->actor_id);
        $this->assertSame(CoreAccounting::MJ, $audit->metadata['number']);
        $this->assertSame('bukti koreksi.jpg', $audit->metadata['file']['filename']);
        $this->assertSame(hash('sha256', $contents), $audit->metadata['file']['sha256']);
    }

    #[Test]
    public function a_file_that_is_not_a_small_image_never_reaches_the_esb(): void
    {
        Http::fake();

        $this->upload(UploadedFile::fake()->createWithContent('nota.pdf', "%PDF-1.7\n rest"))->assertSessionHasErrors('file');
        $this->upload(UploadedFile::fake()->image('big.png')->size(2049))->assertSessionHasErrors('file');
        $this->actingAs($this->manager())
            ->post(route('memorial-journals.images.store', CoreAccounting::MJ), [])
            ->assertSessionHasErrors('file');
        $this->upload(number: 'upload')->assertNotFound();

        Http::assertNothingSent();
    }

    #[Test]
    public function an_unconfirmed_upload_is_audited_once(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts): never {
            $attempts++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->upload()->assertSessionHas('inertia.flash_data.unconfirmedWrite.reference');

        $this->assertSame(1, $attempts);

        $audit = AuditLog::query()->sole();
        $this->assertSame('memorial-journal.write-unconfirmed', $audit->action);
        $this->assertSame('attach', $audit->metadata['operation']);
    }

    #[Test]
    public function an_upload_shares_the_journals_write_lock(): void
    {
        Http::fake();

        $lock = Cache::lock('memorial-journal-write:'.CoreAccounting::MJ, 60);
        $lock->get();

        try {
            $this->upload()->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function the_spreadsheet_upload_and_export_are_not_offered(): void
    {
        // [ESB-046]: the vendor's file field and typeID are undocumented.
        $this->assertFalse(Route::has('memorial-journals.form-lines'));

        // The export is the gateway's own CSV of the list (ADR-031), never
        // the vendor's export file.
        $this->assertSame(
            ListExportController::class.'@memorialJournals',
            Route::getRoutes()->getByName('memorial-journals.export')?->getActionName(),
        );
    }
}
