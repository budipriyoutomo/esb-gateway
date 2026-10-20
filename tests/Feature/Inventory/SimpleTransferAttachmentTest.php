<?php

namespace Tests\Feature\Inventory;

use App\Domain\Canonical\InventoryKind;
use Illuminate\Http\UploadedFile;
use Tests\Fixtures\CoreInventory;

/**
 * Simple transfer attachments (S4): PDF and images of at most 2 MB; the
 * upload keeps only the "existing" URLs it is sent, and the vendor has no
 * delete.
 */
class SimpleTransferAttachmentTest extends InventoryAttachmentTestCase
{
    protected function kind(): InventoryKind
    {
        return InventoryKind::SimpleTransfers;
    }

    protected function upstreamPath(): string
    {
        return '/simple-transfer';
    }

    protected function number(): string
    {
        return CoreInventory::ST;
    }

    protected function auditPrefix(): string
    {
        return 'simple-transfer';
    }

    protected function detailWithAttachments(int $count): array
    {
        return CoreInventory::stDetail(['imageAttachments' => array_map(
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
        return false;
    }

    protected function acceptedFile(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('surat-jalan.png', "\x89PNG\r\n\x1A\n rest");
    }

    protected function rejectedFile(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('surat-jalan.docx', "PK\x03\x04 rest");
    }
}
