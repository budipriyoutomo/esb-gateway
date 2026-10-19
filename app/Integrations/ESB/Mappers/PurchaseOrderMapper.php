<?php

namespace App\Integrations\ESB\Mappers;

use App\Domain\Canonical\PurchaseOrder;
use App\Domain\Canonical\PurchaseOrderLine;
use App\Domain\Canonical\PurchaseOrderLinkedDocument;
use App\Domain\Canonical\PurchaseOrderSummary;
use App\Integrations\ESB\Clients\PurchaseOrderClient;

/**
 * Maps the ESB's purchase order list rows and detail result onto the
 * canonical shapes.
 *
 * ESB responses are untrusted input (docs/10-security.md), so every field is
 * read defensively. Empty strings become null, and a reference id of 0 means
 * "none" -- the vendor's placeholders use both for unset values.
 */
class PurchaseOrderMapper
{
    /**
     * @param  array<string, mixed>  $row
     */
    public function summaryFromRow(array $row): PurchaseOrderSummary
    {
        return new PurchaseOrderSummary(
            provider: PurchaseOrderClient::PROVIDER,
            number: $this->stringOr($row['purchaseNum'] ?? null, ''),
            date: $this->nullableString($row['purchaseDate'] ?? null),
            requiredDate: $this->nullableString($row['requiredDate'] ?? null),
            branchId: $this->nullableId($row['branchID'] ?? null),
            branchName: $this->nullableString($row['branchName'] ?? null),
            supplierId: $this->nullableId($row['supplierID'] ?? null),
            supplierName: $this->nullableString($row['supplierName'] ?? null),
            currencyId: $this->nullableId($row['currencyID'] ?? null),
            currencySign: $this->nullableString($row['currencySign'] ?? null),
            total: $this->nullableFloat($row['purchaseTotal'] ?? null),
            statusId: $this->nullableInt($row['statusID'] ?? null),
            statusName: $this->nullableString($row['statusName'] ?? null),
            purchaseRequestNumbers: $this->nullableString($row['purchaseRequestNums'] ?? null),
            createdByName: $this->nullableString($row['createdBy'] ?? null),
            notes: $this->nullableString($row['additionalInfo'] ?? null),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<PurchaseOrderSummary>
     */
    public function summariesFromRows(array $rows): array
    {
        return array_map(fn (array $row): PurchaseOrderSummary => $this->summaryFromRow($row), $rows);
    }

    /**
     * @param  array<mixed>  $result
     */
    public function fromDetail(array $result): PurchaseOrder
    {
        $access = is_array($result['access'] ?? null) ? $result['access'] : [];

        return new PurchaseOrder(
            provider: PurchaseOrderClient::PROVIDER,
            number: $this->stringOr($result['purchaseNum'] ?? null, ''),
            date: $this->nullableString($result['purchaseDate'] ?? null),
            requiredDate: $this->nullableString($result['requiredDate'] ?? null),
            purchaseTypeName: $this->nullableString($result['purchaseTypeName'] ?? null),
            branchId: $this->nullableId($result['branchID'] ?? null),
            branchName: $this->nullableString($result['branchName'] ?? null),
            supplierId: $this->nullableId($result['supplierID'] ?? null),
            supplierName: $this->nullableString($result['supplierName'] ?? null),
            supplierBranchName: $this->nullableString($result['supplierBranchName'] ?? null),
            supplierAddress: $this->nullableString($result['supplierAddress'] ?? null),
            supplierPhone: $this->nullableString($result['supplierPhone1'] ?? null),
            contactName: $this->nullableString($result['picName'] ?? null),
            currencyId: $this->nullableId($result['currencyID'] ?? null),
            currencyName: $this->nullableString($result['currencyName'] ?? null),
            currencySign: $this->nullableString($result['currencySign'] ?? null),
            rate: $this->nullableFloat($result['rate'] ?? null),
            dueDays: $this->nullableInt($result['dueDay'] ?? null),
            costCenterName: $this->nullableString($result['costCenterName'] ?? null),
            projectName: $this->nullableString($result['projectName'] ?? null),
            total: $this->nullableFloat($result['purchaseTotal'] ?? null),
            statusId: $this->nullableInt($result['statusID'] ?? null),
            statusName: $this->nullableString($result['statusName'] ?? null),
            notes: $this->nullableString($result['additionalInfo'] ?? null),
            footNote: $this->nullableString($result['footNote'] ?? null),
            purchaseRequestNumbers: $this->nullableString($result['purchaseRequestNums'] ?? null),
            createdByName: $this->personName($result['creator'] ?? null, $result['createdBy'] ?? null),
            createdAt: $this->nullableString($result['createdDate'] ?? null),
            editedByName: $this->personName($result['editor'] ?? null, $result['editedBy'] ?? null),
            editedAt: $this->nullableString($result['editedDate'] ?? null),
            authorizedByName: $this->personName($result['authorizer'] ?? null, $result['authorizedBy'] ?? null),
            authorizedAt: $this->nullableString($result['authorizedDate'] ?? null),
            canEdit: $this->nullableBool($access['canEdit'] ?? null),
            canDelete: $this->nullableBool($access['canDelete'] ?? null),
            lines: $this->linesFrom($result['purchaseDetails'] ?? null),
            goodsReceipts: $this->linkedFrom($result['goodsReceiptHead'] ?? null, 'goodsReceiptNum', 'goodsReceiptDate'),
            purchaseInvoices: $this->linkedFrom($result['purchaseInvoiceHead'] ?? null, 'purchaseInvoiceNum', 'purchaseInvoiceDate', 'invoiceTotal'),
        );
    }

    /**
     * The detail result's `creator` / `editor` / `authorizer` objects, with
     * the plain `createdBy` / `editedBy` / `authorizedBy` username as the
     * fallback.
     */
    private function personName(mixed $person, mixed $username): ?string
    {
        if (is_array($person)) {
            $name = $this->nullableString($person['fullName'] ?? null)
                ?? $this->nullableString($person['username'] ?? null);

            if ($name !== null) {
                return $name;
            }
        }

        return $this->nullableString($username);
    }

    /**
     * @return list<PurchaseOrderLine>
     */
    private function linesFrom(mixed $details): array
    {
        if (! is_array($details)) {
            return [];
        }

        $lines = [];

        foreach ($details as $row) {
            if (! is_array($row)) {
                continue;
            }

            $lines[] = new PurchaseOrderLine(
                productDetailId: $this->nullableId($row['productDetailID'] ?? null),
                productCode: $this->stringOr($row['productCode'] ?? null, ''),
                productName: $this->stringOr($row['productName'] ?? null, ''),
                qty: $this->nullableFloat($row['qty'] ?? null),
                requestedQty: $this->nullableFloat($row['requestQty'] ?? null),
                uomName: $this->nullableString($row['uomName'] ?? null),
                price: $this->nullableFloat($row['price'] ?? null),
                discount: $this->nullableFloat($row['discount'] ?? null),
                discountPercent: $this->nullableFloat($row['discountPercent'] ?? null),
                total: $this->nullableFloat($row['total'] ?? null),
                notes: $this->nullableString($row['notes'] ?? null),
            );
        }

        return $lines;
    }

    /**
     * A linked document without a number cannot be identified -- the
     * vendor's placeholder is one -- so it is skipped.
     *
     * @return list<PurchaseOrderLinkedDocument>
     */
    private function linkedFrom(mixed $rows, string $numberKey, string $dateKey, ?string $totalKey = null): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $documents = [];

        foreach ($rows as $row) {
            $number = is_array($row) ? $this->nullableString($row[$numberKey] ?? null) : null;

            if ($number === null) {
                continue;
            }

            $documents[] = new PurchaseOrderLinkedDocument(
                number: $number,
                date: $this->nullableString($row[$dateKey] ?? null),
                statusName: $this->nullableString($row['statusName'] ?? null),
                total: $totalKey === null ? null : $this->nullableFloat($row[$totalKey] ?? null),
            );
        }

        return $documents;
    }

    private function nullableBool(mixed $value): ?bool
    {
        return is_bool($value) ? $value : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * A reference id, where the vendor's 0 means "none attached".
     */
    private function nullableId(mixed $value): ?int
    {
        $id = $this->nullableInt($value);

        return $id !== null && $id > 0 ? $id : null;
    }

    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function stringOr(mixed $value, string $default): string
    {
        return is_string($value) ? $value : $default;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
