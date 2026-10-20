<?php

namespace Tests\Feature\Inventory;

use App\Domain\Canonical\InventoryKind;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreInventory;

/**
 * Goods receipts (TODO-modules.md §3, S2): authorize / reject / delete.
 */
class GoodsReceiptWriteTest extends InventoryWriteTestCase
{
    protected function kind(): InventoryKind
    {
        return InventoryKind::GoodsReceipts;
    }

    protected function upstreamPath(): string
    {
        return '/inventory/goods-receipt';
    }

    protected function number(): string
    {
        return CoreInventory::GR;
    }

    protected function auditPrefix(): string
    {
        return 'goods-receipt';
    }

    #[Test]
    public function receipts_take_no_attachments_here(): void
    {
        // The vendor's only upload is asset images (POST /{refNum}/asset-images).
        $this->assertFalse(Route::has('goods-receipts.attachments.store'));
        $this->assertFalse(Route::has('goods-receipts.attachments.destroy'));
    }
}
