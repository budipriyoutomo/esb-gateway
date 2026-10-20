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
 * Goods deliveries ([ESB-033], S1), with the outstanding quantity per line of the reference.
 */
class GoodsDeliveryTest extends InventoryDocumentTestCase
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

    protected function numberParam(): string
    {
        return 'goodsDeliveryNum';
    }

    protected function branchParam(): ?string
    {
        return 'branchID';
    }

    protected function row(): array
    {
        return CoreInventory::gdRow();
    }

    protected function detailResponse(): array
    {
        return CoreInventory::gdDetail();
    }

    protected function extraReads(): array
    {
        return ['/get-details-outstanding-qty' => CoreInventory::gdOutstanding()];
    }

    #[Test]
    public function the_detail_asks_the_outstanding_quantity_of_its_reference(): void
    {
        $sent = [];
        $this->fakeUpstream($sent);

        $this->show($this->number())
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('document.lines.0.outstandingQty', 5));

        $outstanding = collect($sent)->first(fn (string $request): bool => str_contains($request, 'outstanding'));
        $this->assertNotNull($outstanding);
        parse_str((string) parse_url($outstanding, PHP_URL_QUERY), $query);
        $this->assertSame(['goodsDeliveryNum' => CoreInventory::GD, 'refNum' => CoreInventory::GTR, 'transType' => 'Transfer Request'], $query);
    }

    #[Test]
    public function a_delivery_without_a_reference_asks_nothing_more(): void
    {
        $sent = [];
        $this->fakeUpstream($sent, CoreInventory::gdDetail(['referenceNumber' => '', 'transType' => '']));

        $this->show($this->number())->assertOk();

        $this->assertCount(1, $sent);
    }

    #[Test]
    public function a_failed_outstanding_read_leaves_the_column_empty(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'outstanding')) {
                throw new ConnectionException('cURL error 28: Operation timed out');
            }

            return Http::response(CoreInventory::gdDetail());
        });

        $this->show($this->number())
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('document.lines.0.outstandingQty', null));
    }
}
