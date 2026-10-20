<?php

namespace Tests\Feature\Inventory;

use App\Domain\Canonical\InventoryKind;
use Tests\Fixtures\CoreInventory;

/**
 * Goods deliveries (TODO-modules.md §3, S2): authorize / reject / delete.
 */
class GoodsDeliveryWriteTest extends InventoryWriteTestCase
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
}
