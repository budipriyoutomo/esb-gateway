<?php

namespace Tests\Feature\Inventory;

use App\Domain\Canonical\InventoryKind;
use Illuminate\Http\UploadedFile;
use Tests\Fixtures\CoreInventory;

/**
 * Goods delivery attachments (S4): PDF and images; the upload keeps only
 * the "existing" URLs it is sent.
 */
class GoodsDeliveryAttachmentTest extends InventoryAttachmentTestCase
{
    protected function kind(): InventoryKind
    {
        return InventoryKind::GoodsDeliveries;
    }

    protected function upstreamPath(): string
    {
        return '/inventory/goods-delivery';
    }

    protected function number(): string
    {
        return CoreInventory::GD;
    }

    protected function auditPrefix(): string
    {
        return 'goods-delivery';
    }

    protected function detailWithAttachments(int $count): array
    {
        return CoreInventory::gdDetail(['goodsDeliveryURLAttachments' => array_map(
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
