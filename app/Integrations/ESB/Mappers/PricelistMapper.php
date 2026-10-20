<?php

namespace App\Integrations\ESB\Mappers;

use App\Domain\Canonical\BranchScope;
use App\Domain\Canonical\PricelistDraft;
use App\Domain\Canonical\PricelistDraftLine;
use App\Domain\Canonical\PricelistEntry;
use App\Domain\Canonical\PurchasePrice;
use App\Domain\Canonical\TempPricelist;
use App\Domain\Canonical\TempPricelistLine;
use App\Domain\Canonical\TempPricelistSummary;
use App\Integrations\ESB\Clients\PricelistClient;
use App\Integrations\ESB\Mappers\Concerns\ReadsEsbValues;

/**
 * Maps ESB Core pricelists onto the canonical pricelist DTOs ([ESB-027]).
 */
class PricelistMapper
{
    use ReadsEsbValues;

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<PricelistEntry>
     */
    public function entriesFromRows(array $rows): array
    {
        return array_map(fn (array $row): PricelistEntry => $this->entryFromRow($row), $rows);
    }

    /**
     * @param  array<mixed>  $row
     */
    public function entryFromRow(array $row): PricelistEntry
    {
        return new PricelistEntry(
            provider: PricelistClient::PROVIDER,
            rowId: $this->nullableId($row['ID'] ?? null),
            pricelistNumber: $this->nullableString($row['pricelistNum'] ?? null),
            productDetailId: $this->nullableId($row['productDetailID'] ?? null),
            productCode: $this->nullableString($row['productCode'] ?? null),
            productName: $this->nullableString($row['productName'] ?? null),
            unit: $this->nullableString($row['unit'] ?? null),
            supplierId: $this->nullableId($row['supplierID'] ?? null),
            supplierName: $this->nullableString($row['supplierName'] ?? null),
            currencyName: $this->nullableString($row['currencyName'] ?? null),
            price: $this->amount($row['price'] ?? null),
            priceDate: $this->nullableString($row['priceDate'] ?? null),
            expireDate: $this->nullableString($row['expireDate'] ?? null),
            branches: $this->scope($row['applicableBranch'] ?? null),
        );
    }

    /**
     * Rows without a number are dropped: nothing could open them.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<TempPricelistSummary>
     */
    public function tempSummariesFromRows(array $rows): array
    {
        $summaries = [];

        foreach ($rows as $row) {
            if ($this->nullableString($row['pricelistNum'] ?? null) !== null) {
                $summaries[] = $this->tempSummaryFromRow($row);
            }
        }

        return $summaries;
    }

    /**
     * @param  array<mixed>  $row
     */
    public function tempSummaryFromRow(array $row): TempPricelistSummary
    {
        return new TempPricelistSummary(
            provider: PricelistClient::PROVIDER,
            number: $this->stringOr($row['pricelistNum'] ?? null, ''),
            supplierName: $this->nullableString($row['supplierName'] ?? null),
            currencyName: $this->nullableString($row['currencyName'] ?? null),
            priceDate: $this->nullableString($row['priceDate'] ?? null),
            expireDate: $this->nullableString($row['expireDate'] ?? null),
            statusId: $this->nullableId($row['statusID'] ?? null),
            statusName: $this->nullableString($row['statusName'] ?? null),
            branches: $this->scope($row['applicableBranch'] ?? null),
        );
    }

    /**
     * The detail result, or null when it carries no number.
     *
     * @param  array<mixed>  $result
     */
    public function tempFromDetail(array $result): ?TempPricelist
    {
        $number = $this->nullableString($result['pricelistNum'] ?? null);

        if ($number === null) {
            return null;
        }

        $lines = [];

        foreach (is_array($result['pricelistDetails'] ?? null) ? $result['pricelistDetails'] : [] as $line) {
            if (is_array($line)) {
                $lines[] = new TempPricelistLine(
                    productDetailId: $this->nullableId($line['productDetailID'] ?? null),
                    productCode: $this->nullableString($line['productCode'] ?? null),
                    productName: $this->nullableString($line['productName'] ?? null),
                    unitName: $this->nullableString($line['unitName'] ?? null),
                    price: $this->amount($line['price'] ?? null),
                    lastPrice: $this->amount($line['lastPrice'] ?? null),
                );
            }
        }

        return new TempPricelist(
            provider: PricelistClient::PROVIDER,
            number: $number,
            supplierId: $this->nullableId($result['supplierID'] ?? null),
            supplierName: $this->nullableString($result['supplierName'] ?? null),
            currencyName: $this->nullableString($result['currencyName'] ?? null),
            currencyId: $this->nullableId($result['currencyID'] ?? null),
            priceDate: $this->nullableString($result['priceDate'] ?? null),
            expireDate: $this->nullableString($result['expireDate'] ?? null),
            statusId: $this->nullableId($result['statusID'] ?? null),
            statusName: $this->nullableString($result['statusName'] ?? null),
            branches: $this->scope($result['applicableBranch'] ?? null),
            lines: $lines,
            createdBy: $this->nullableString($result['createdBy'] ?? null),
            createdAt: $this->nullableString($result['createdDate'] ?? null),
            editedBy: $this->nullableString($result['editedBy'] ?? null),
            editedAt: $this->nullableString($result['editedDate'] ?? null),
            rejectedBy: $this->nullableString($result['rejectedBy'] ?? null),
            rejectedAt: $this->nullableString($result['rejectedDate'] ?? null),
        );
    }

    /**
     * A price may legitimately be 0, so only non-numbers are none.
     */
    private function amount(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function scope(mixed $scope): BranchScope
    {
        if (! is_array($scope)) {
            return new BranchScope(all: null);
        }

        $names = [];
        $ids = [];

        foreach (is_array($scope['branches'] ?? null) ? $scope['branches'] : [] as $branch) {
            $name = is_array($branch) ? $this->nullableString($branch['branchName'] ?? null) : null;
            $id = is_array($branch) ? $this->nullableId($branch['branchID'] ?? null) : null;

            if ($name !== null) {
                $names[] = $name;
            }

            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return new BranchScope(
            all: match ($scope['type'] ?? null) {
                'ALL' => true,
                'SPECIFIC' => false,
                default => null,
            },
            names: $names,
            ids: $ids,
        );
    }

    /**
     * The body of POST /pricelist and PUT /pricelist/temp/{num}. No branches
     * is the vendor's "all branches" (an empty branchIDs).
     *
     * @return array<string, mixed>
     */
    public function requestFrom(PricelistDraft $draft): array
    {
        return [
            'supplierID' => $draft->supplierId,
            'currencyID' => $draft->currencyId,
            'priceDate' => $draft->priceDate,
            'expireDate' => $draft->expireDate,
            'branchIDs' => $draft->branchIds,
            'pricelistDetails' => array_map(
                static fn (PricelistDraftLine $line): array => ['productDetailID' => $line->productDetailId, 'price' => $line->price],
                $draft->lines,
            ),
        ];
    }

    /**
     * GET /pricelist/last-price rows as productDetailId => price. The vendor
     * answers 0 for a product without an active price, so 0 is "no price".
     *
     * @param  array<mixed>  $rows
     * @return array<int, int|float|null>
     */
    public function lastPricesFrom(array $rows): array
    {
        $prices = [];

        foreach ($rows as $row) {
            $id = is_array($row) ? $this->nullableId($row['productDetailID'] ?? null) : null;

            if ($id !== null) {
                $price = is_numeric($row['price'] ?? null) ? $row['price'] + 0 : 0;
                $prices[$id] = $price == 0 ? null : $price;
            }
        }

        return $prices;
    }

    /**
     * GET /pricelist/history/purchase-invoice rows; one without an invoice
     * number (the vendor's zero-valued placeholder) is dropped.
     *
     * @param  array<mixed>  $rows
     * @return list<PurchasePrice>
     */
    public function purchasePricesFrom(array $rows): array
    {
        $prices = [];

        foreach ($rows as $row) {
            $number = is_array($row) ? $this->nullableString($row['purchaseInvoiceNum'] ?? null) : null;

            if ($number === null) {
                continue;
            }

            $prices[] = new PurchasePrice(
                invoiceNumber: $number,
                invoiceDate: $this->nullableString($row['purchaseInvoiceDate'] ?? null),
                price: is_numeric($row['price'] ?? null) ? $row['price'] + 0 : null,
                supplierId: $this->nullableId($row['supplierID'] ?? null),
                supplierName: $this->nullableString($row['supplierName'] ?? null),
            );
        }

        return $prices;
    }
}
