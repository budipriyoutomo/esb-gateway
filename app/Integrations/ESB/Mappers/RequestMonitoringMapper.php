<?php

namespace App\Integrations\ESB\Mappers;

use App\Domain\Canonical\UnorderedRequestItem;
use App\Integrations\ESB\Mappers\Concerns\ReadsEsbValues;

/**
 * POST /purchase/request-monitoring rows onto UnorderedRequestItem
 * ([ESB-053], ADR-034). A row without a request number, a product or a
 * numeric quantity cannot be reported and is dropped.
 */
class RequestMonitoringMapper
{
    use ReadsEsbValues;

    /**
     * @param  array<mixed>  $rows
     * @return list<UnorderedRequestItem>
     */
    public function itemsFromRows(array $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            $item = is_array($row) ? $this->itemFromRow($row) : null;

            if ($item !== null) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @param  array<mixed>  $row
     */
    private function itemFromRow(array $row): ?UnorderedRequestItem
    {
        $number = $this->nullableString($row['purchaseRequestNum'] ?? null);
        $productDetailId = $this->nullableId($row['productDetailID'] ?? null);
        $qty = $row['sumQty'] ?? null;

        if ($number === null || $productDetailId === null || ! is_numeric($qty)) {
            return null;
        }

        return new UnorderedRequestItem(
            purchaseRequestNumber: $number,
            purchaseRequestDate: $this->nullableString($row['purchaseRequestDate'] ?? null),
            requiredDate: $this->nullableString($row['requiredDate'] ?? null),
            branchId: $this->nullableId($row['branchID'] ?? null),
            branchName: $this->nullableString($row['branchName'] ?? null),
            productDetailId: $productDetailId,
            productCode: $this->nullableString($row['productCode'] ?? null),
            productName: $this->nullableString($row['productName'] ?? null),
            uomName: $this->nullableString($row['uomName'] ?? null),
            unorderedQty: (float) $qty,
            requestProcessId: $this->nullableId($row['requestProcessID'] ?? null),
            requestProcessName: $this->nullableString($row['requestProcessName'] ?? null),
            supplierName: $this->nullableString($row['supplierName'] ?? null),
        );
    }
}
