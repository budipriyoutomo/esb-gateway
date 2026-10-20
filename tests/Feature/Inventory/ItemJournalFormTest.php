<?php

namespace Tests\Feature\Inventory;

use App\Domain\Canonical\InventoryKind;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreInventory;

/**
 * Item journals (TODO-modules.md §3, S3): stock adjustments at one branch
 * and location, each line with an optional purpose and cost (HPP). The
 * update is a PUT and keeps the request template.
 */
class ItemJournalFormTest extends InventoryFormTestCase
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

    protected function auditPrefix(): string
    {
        return 'item-journal';
    }

    protected function updateMethod(): string
    {
        return 'PUT';
    }

    protected function validInput(): array
    {
        return [
            'date' => '2026-03-23',
            'branchId' => 3,
            'locationId' => 300,
            'notes' => 'Stock opname',
            'lines' => [
                ['productDetailId' => 501, 'qty' => -3, 'purposeId' => 2, 'cost' => 12000],
                ['productDetailId' => 502, 'qty' => 1],
            ],
        ];
    }

    protected function expectedCreateBody(): array
    {
        return [
            'itemJournalDate' => '2026-03-23',
            'branchID' => 3,
            'locationID' => 300,
            'additionalInfo' => 'Stock opname',
            'itemJournalDetails' => [
                ['productDetailID' => 501, 'qty' => -3, 'purposeID' => 2, 'hpp' => 12000],
                ['productDetailID' => 502, 'qty' => 1],
            ],
        ];
    }

    protected function expectedUpdateBody(): array
    {
        return [...$this->expectedCreateBody(), 'requestTemplateID' => 12];
    }

    protected function detailResponse(): array
    {
        return CoreInventory::ijDetail(['requestTemplateID' => 12]);
    }

    protected function assetDetailResponse(): ?array
    {
        return null;
    }

    protected function createdResult(): ?array
    {
        return ['itemJournalNum' => CoreInventory::IJ];
    }

    protected function vendorDateField(): string
    {
        return 'itemJournalDate';
    }

    #[Test]
    public function the_vendors_rules_are_checked_before_the_call(): void
    {
        Http::fake();

        // Not in the future (Asia/Jakarta); each product detail once.
        $tomorrow = Carbon::now('Asia/Jakarta')->addDay()->format('Y-m-d');

        $this->store([...$this->validInput(), 'date' => $tomorrow])->assertSessionHasErrors('date');
        $this->store([...$this->validInput(), 'lines' => [['productDetailId' => 501, 'qty' => 1], ['productDetailId' => 501, 'qty' => 2]]])
            ->assertSessionHasErrors('lines.1.productDetailId');
        $this->store([...$this->validInput(), 'locationId' => null])->assertSessionHasErrors('locationId');
        $this->store([...$this->validInput(), 'lines' => [['productDetailId' => 501, 'qty' => 1, 'cost' => -1]]])
            ->assertSessionHasErrors('lines.0.cost');

        Http::assertNothingSent();
    }
}
