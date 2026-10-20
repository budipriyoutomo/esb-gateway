<?php

namespace Tests\Feature\Inventory;

use App\Domain\Canonical\InventoryKind;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreInventory;

/**
 * Simple transfers (TODO-modules.md §3, S3): products from one location to
 * another. The branch on the form only narrows the pickers and is not sent.
 * The update is a PUT and keeps the cost center and project.
 */
class SimpleTransferFormTest extends InventoryFormTestCase
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

    protected function updateMethod(): string
    {
        return 'PUT';
    }

    protected function validInput(): array
    {
        return [
            'date' => '2026-03-24',
            'branchId' => 3,
            'locationId' => 300,
            'destinationLocationId' => 301,
            'notes' => 'Ke dapur',
            'lines' => [
                ['productDetailId' => 501, 'qty' => 10],
            ],
        ];
    }

    protected function expectedCreateBody(): array
    {
        return [
            'simpleTransferDate' => '2026-03-24',
            'originLocationID' => 300,
            'destinationLocationID' => 301,
            'additionalInfo' => 'Ke dapur',
            'simpleTransferDetails' => [
                ['productDetailID' => 501, 'qty' => 10],
            ],
        ];
    }

    protected function expectedUpdateBody(): array
    {
        return [...$this->expectedCreateBody(), 'costCenterID' => 4, 'projectID' => 9];
    }

    protected function detailResponse(): array
    {
        return CoreInventory::stDetail(['costCenterID' => 4, 'projectID' => 9]);
    }

    protected function assetDetailResponse(): ?array
    {
        return CoreInventory::stDetail(['simpleTransferAssets' => ['AST-1']]);
    }

    protected function createdResult(): ?array
    {
        // The vendor documents no number in the answer.
        return null;
    }

    protected function vendorDateField(): string
    {
        return 'simpleTransferDate';
    }

    #[Test]
    public function the_vendors_rules_are_checked_before_the_call(): void
    {
        Http::fake();

        // qty greater than 0, decimal(20,4).
        $this->store([...$this->validInput(), 'lines' => [['productDetailId' => 501, 'qty' => 0]]])
            ->assertSessionHasErrors('lines.0.qty');
        $this->store([...$this->validInput(), 'lines' => [['productDetailId' => 501, 'qty' => 1.12345]]])
            ->assertSessionHasErrors('lines.0.qty');
        $this->store([...$this->validInput(), 'destinationLocationId' => null])
            ->assertSessionHasErrors('destinationLocationId');

        Http::assertNothingSent();
    }
}
