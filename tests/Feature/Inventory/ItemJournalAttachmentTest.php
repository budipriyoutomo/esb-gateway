<?php

namespace Tests\Feature\Inventory;

use App\Domain\Canonical\InventoryKind;
use Illuminate\Http\UploadedFile;
use Tests\Fixtures\CoreInventory;

/**
 * Item journal attachments (S4): the upload keeps only the "existing"
 * URLs it is sent.
 */
class ItemJournalAttachmentTest extends InventoryAttachmentTestCase
{
    protected function kind(): InventoryKind
    {
        return InventoryKind::ItemJournals;
    }

    protected function upstreamPath(): string
    {
        return '/inventory/item-journal';
    }

    protected function number(): string
    {
        return CoreInventory::IJ;
    }

    protected function auditPrefix(): string
    {
        return 'item-journal';
    }

    protected function detailWithAttachments(int $count): array
    {
        return CoreInventory::ijDetail(['imageAttachments' => array_map(
            static fn (int $i): string => 'https://cdn.example.test/'.$i.'.jpg',
            $count === 0 ? [] : range(1, $count),
        )]);
    }

    protected function replacesExisting(): bool
    {
        return true;
    }

    protected function removable(): bool
    {
        return true;
    }

    protected function acceptedFile(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('surat-jalan.pdf', "%PDF-1.7\n rest");
    }

    protected function rejectedFile(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('surat-jalan.docx', "PK\x03\x04 rest");
    }
}
