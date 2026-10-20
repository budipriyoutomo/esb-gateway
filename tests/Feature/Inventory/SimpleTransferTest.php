<?php

namespace Tests\Feature\Inventory;

use App\Domain\Canonical\InventoryKind;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreInventory;

/**
 * Simple transfers ([ESB-033], S1), with the stock check. The list has no branch filter.
 */
class SimpleTransferTest extends InventoryDocumentTestCase
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

    protected function numberParam(): string
    {
        return 'simpleTransferNum';
    }

    protected function branchParam(): ?string
    {
        return null;
    }

    protected function row(): array
    {
        return CoreInventory::stRow();
    }

    protected function detailResponse(): array
    {
        return CoreInventory::stDetail();
    }

    protected function extraReads(): array
    {
        return ['/simple-transfer/stock-check/' => CoreInventory::stockCheck(true, '/simple-transfer/stock-check/'.CoreInventory::ST)];
    }

    #[Test]
    public function the_detail_says_whether_the_stock_has_been_used_since(): void
    {
        $sent = [];
        $this->fakeUpstream($sent);

        $this->show($this->number())
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('document.stockUsed', true));

        $this->assertContains('/core-int/simple-transfer/stock-check/'.CoreInventory::ST.'?', $sent);
    }

    #[Test]
    public function a_failed_stock_check_leaves_it_unknown(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'stock-check')) {
                throw new ConnectionException('cURL error 28: Operation timed out');
            }

            return Http::response($this->detailResponse());
        });

        $this->show($this->number())
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('document.stockUsed', null));
    }
}
