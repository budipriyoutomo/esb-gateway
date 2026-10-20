<?php

namespace Tests\Feature\Inventory;

use App\Domain\Canonical\InventoryKind;
use Tests\Fixtures\CoreInventory;

/**
 * Simple transfers (TODO-modules.md §3, S2): authorize / reject / delete.
 */
class SimpleTransferWriteTest extends InventoryWriteTestCase
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
}
