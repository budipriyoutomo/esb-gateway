<?php

namespace Tests\Feature\Inventory;

use App\Domain\Canonical\InventoryKind;
use Tests\Fixtures\CoreInventory;

/**
 * Item journals ([ESB-033], S1).
 */
class ItemJournalTest extends InventoryDocumentTestCase
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

    protected function numberParam(): string
    {
        return 'itemJournalNum';
    }

    protected function branchParam(): ?string
    {
        return 'branchID';
    }

    protected function row(): array
    {
        return CoreInventory::ijRow();
    }

    protected function detailResponse(): array
    {
        return CoreInventory::ijDetail();
    }
}
