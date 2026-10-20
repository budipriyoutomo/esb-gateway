<?php

namespace Tests\Feature\Inventory;

use App\Domain\Canonical\InventoryKind;
use Illuminate\Http\UploadedFile;
use Tests\Fixtures\CoreInventory;

/**
 * Goods transfer request attachments (S4): images only; the vendor's
 * upload takes no "existing" list, so it is taken to add to what is there
 * (as the supplier upload, [ESB-032]).
 */
class GoodsTransferRequestAttachmentTest extends InventoryAttachmentTestCase
{
    protected function kind(): InventoryKind
    {
        return InventoryKind::GoodsTransferRequests;
    }

    protected function upstreamPath(): string
    {
        return '/inventory/goods-transfer-request';
    }

    protected function number(): string
    {
        return CoreInventory::GTR;
    }

    protected function auditPrefix(): string
    {
        return 'goods-transfer-request';
    }

    protected function detailWithAttachments(int $count): array
    {
        return CoreInventory::gtrDetail(['imageAttachments' => array_map(
            static fn (int $i): string => 'https://cdn.example.test/'.$i.'.jpg',
            $count === 0 ? [] : range(1, $count),
        )]);
    }

    protected function replacesExisting(): bool
    {
        return false;
    }

    protected function removable(): bool
    {
        return true;
    }

    protected function acceptedFile(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('surat-jalan.png', "\x89PNG\r\n\x1A\n rest");
    }

    protected function rejectedFile(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('surat-jalan.pdf', "%PDF-1.7\n rest");
    }
}
