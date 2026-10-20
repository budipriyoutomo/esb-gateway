<?php

namespace Tests\Feature\Inventory;

use App\Domain\Canonical\InventoryKind;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreInventory;

/**
 * Goods transfer requests ([ESB-033], S1). The only list with a free-text search.
 */
class GoodsTransferRequestTest extends InventoryDocumentTestCase
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

    protected function numberParam(): string
    {
        return 'transferNum';
    }

    protected function branchParam(): ?string
    {
        return 'originBranchID';
    }

    protected function row(): array
    {
        return CoreInventory::gtrRow();
    }

    protected function detailResponse(): array
    {
        return CoreInventory::gtrDetail();
    }

    #[Test]
    public function a_search_is_sent_as_the_vendors_index_search(): void
    {
        $sent = [];
        $this->fakeUpstream($sent);

        $this->index(['search' => 'restock'])->assertOk();

        $this->assertStringContainsString('indexSearch=restock', $sent[0]);
    }
}
