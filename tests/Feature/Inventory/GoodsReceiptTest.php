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
 * Goods receipts ([ESB-033], S1), with the stock check.
 */
class GoodsReceiptTest extends InventoryDocumentTestCase
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

    protected function numberParam(): string
    {
        return 'goodsReceiptNum';
    }

    protected function branchParam(): ?string
    {
        return 'branchID';
    }

    protected function row(): array
    {
        return CoreInventory::grRow();
    }

    protected function detailResponse(): array
    {
        return CoreInventory::grDetail();
    }

    protected function extraReads(): array
    {
        return ['/inventory/goods-receipt/stock-check/' => CoreInventory::stockCheck(true, '/inventory/goods-receipt/stock-check/'.CoreInventory::GR)];
    }

    #[Test]
    public function pending_receipts_are_listed_by_their_reference_and_can_be_filtered(): void
    {
        Http::fake(fn () => Http::response(CoreInventory::listResponse($this->upstreamPath(), [
            CoreInventory::grRow(['goodsReceiptNum' => null, 'statusID' => 33, 'statusName' => 'Pending']),
            CoreInventory::grRow(),
        ], count: 2)));

        $this->actingAs($this->operator())
            ->get('/'.$this->kind()->value)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('documents', 2)
                ->where('documents.0.number', null)
                ->where('documents.0.reference', 'PO202603200001')
                ->where('documents.0.statusName', 'Pending')
                ->where('documents.1.number', CoreInventory::GR)
                ->where('kind.statuses', fn ($statuses) => collect($statuses)->contains(fn ($status) => $status['id'] === 33 && $status['name'] === 'Pending')));
    }

    #[Test]
    public function the_detail_says_whether_the_stock_has_been_used_since(): void
    {
        $sent = [];
        $this->fakeUpstream($sent);

        $this->show($this->number())
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('document.stockUsed', true));

        $this->assertContains('/core-int/inventory/goods-receipt/stock-check/'.CoreInventory::GR.'?', $sent);
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
