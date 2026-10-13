<?php

namespace App\Integrations\ESB\Mappers;

use App\Domain\Canonical\PurchaseInvoice;
use App\Domain\Canonical\PurchaseInvoiceCost;
use App\Domain\Canonical\PurchaseInvoiceLine;
use App\Domain\Canonical\PurchaseInvoiceSummary;
use App\Integrations\ESB\Clients\PurchaseInvoiceClient;

/**
 * Maps the ESB's purchase invoice list rows and detail result onto the
 * canonical shapes.
 *
 * ESB responses are untrusted input (docs/10-security.md), so every field is
 * read defensively. Empty strings become null, and a reference id of 0 means
 * "none" -- the vendor's placeholders use both for unset values.
 */
class PurchaseInvoiceMapper
{
    /**
     * @param  array<string, mixed>  $row
     */
    public function summaryFromRow(array $row): PurchaseInvoiceSummary
    {
        return new PurchaseInvoiceSummary(
            provider: PurchaseInvoiceClient::PROVIDER,
            number: $this->stringOr($row['purchaseInvoiceNum'] ?? null, ''),
            supplierInvoiceNumber: $this->nullableString($row['supplierInvoiceNum'] ?? null),
            date: $this->nullableString($row['purchaseInvoiceDate'] ?? null),
            dueDate: $this->nullableString($row['purchaseInvoiceDueDate'] ?? null),
            branchId: $this->nullableId($row['branchID'] ?? null),
            branchName: $this->nullableString($row['branchName'] ?? null),
            supplierId: $this->nullableId($row['supplierID'] ?? null),
            supplierName: $this->nullableString($row['supplierName'] ?? null),
            currencyId: $this->nullableId($row['currencyID'] ?? null),
            currencySign: $this->nullableString($row['currencySign'] ?? null),
            total: $this->nullableFloat($row['invoiceTotal'] ?? null),
            statusId: $this->nullableInt($row['statusID'] ?? null),
            statusName: $this->nullableString($row['statusName'] ?? null),
            // The row carries both a username and a full name; prefer the name
            // a person would recognise.
            createdByName: $this->nullableString($row['createdByFullName'] ?? null)
                ?? $this->nullableString($row['createdBy'] ?? null),
            notes: $this->nullableString($row['additionalInfo'] ?? null),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<PurchaseInvoiceSummary>
     */
    public function summariesFromRows(array $rows): array
    {
        return array_map(fn (array $row): PurchaseInvoiceSummary => $this->summaryFromRow($row), $rows);
    }

    /**
     * @param  array<mixed>  $result
     */
    public function fromDetail(array $result): PurchaseInvoice
    {
        return new PurchaseInvoice(
            provider: PurchaseInvoiceClient::PROVIDER,
            number: $this->stringOr($result['purchaseInvoiceNum'] ?? null, ''),
            supplierInvoiceNumber: $this->nullableString($result['supplierInvoiceNum'] ?? null),
            taxInvoiceNumber: $this->nullableString($result['vatInvoiceNum'] ?? null),
            date: $this->nullableString($result['purchaseInvoiceDate'] ?? null),
            dueDate: $this->nullableString($result['purchaseInvoiceDueDate'] ?? null),
            dueDays: $this->nullableInt($result['dueDay'] ?? null),
            referenceDate: $this->nullableString($result['refDate'] ?? null),
            branchId: $this->nullableId($result['branchID'] ?? null),
            branchName: $this->nullableString($result['branchName'] ?? null),
            supplierId: $this->nullableId($result['supplierID'] ?? null),
            supplierName: $this->nullableString($result['supplierName'] ?? null),
            currencyId: $this->nullableId($result['currencyID'] ?? null),
            currencyName: $this->nullableString($result['currencyName'] ?? null),
            currencySign: $this->nullableString($result['currencySign'] ?? null),
            rate: $this->nullableFloat($result['rate'] ?? null),
            referenceTotal: $this->nullableFloat($result['referenceTotal'] ?? null),
            costTotal: $this->nullableFloat($result['costTotal'] ?? null),
            total: $this->nullableFloat($result['invoiceTotal'] ?? null),
            statusId: $this->nullableInt($result['statusID'] ?? null),
            statusName: $this->statusName($result),
            notes: $this->nullableString($result['additionalInfo'] ?? null),
            createdByName: $this->personName($result['creator'] ?? null),
            createdAt: $this->nullableString($result['createdDate'] ?? null),
            editedByName: $this->personName($result['editor'] ?? null),
            editedAt: $this->nullableString($result['editedDate'] ?? null),
            authorizedByName: $this->personName($result['authorizer'] ?? null),
            authorizedAt: $this->nullableString($result['authorizedDate'] ?? null),
            lines: $this->linesFrom($result['details'] ?? null),
            costs: $this->costsFrom($result['costs'] ?? null),
        );
    }

    /**
     * The detail result documents only `statusID` at the top level; the label
     * appears under `printData.statusName`. A top-level `statusName`, as the
     * list rows carry, wins if the ESB sends one.
     *
     * TBD: that printData's label is the document status is inferred from the
     * field name, not stated by the vendor.
     *
     * @param  array<mixed>  $result
     */
    private function statusName(array $result): ?string
    {
        $print = $result['printData'] ?? null;

        return $this->nullableString($result['statusName'] ?? null)
            ?? (is_array($print) ? $this->nullableString($print['statusName'] ?? null) : null);
    }

    /**
     * The detail result's `creator` / `editor` / `authorizer` objects.
     */
    private function personName(mixed $person): ?string
    {
        if (! is_array($person)) {
            return null;
        }

        return $this->nullableString($person['fullName'] ?? null)
            ?? $this->nullableString($person['username'] ?? null);
    }

    /**
     * @return list<PurchaseInvoiceLine>
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

            $lines[] = new PurchaseInvoiceLine(
                productDetailId: $this->nullableId($row['productDetailID'] ?? null),
                productCode: $this->stringOr($row['productCode'] ?? null, ''),
                productName: $this->stringOr($row['productName'] ?? null, ''),
                referenceNumber: $this->nullableString($row['refNum'] ?? null),
                referenceDate: $this->nullableString($row['transDate'] ?? null),
                purchaseOrderNumber: $this->nullableString($row['purchaseNumber'] ?? null),
                qty: $this->nullableFloat($row['qty'] ?? null),
                uomName: $this->nullableString($row['uomName'] ?? null),
                price: $this->nullableFloat($row['price'] ?? null),
                discount: $this->nullableFloat($row['discount'] ?? null),
                discountPercent: $this->nullableFloat($row['discountPercent'] ?? null),
                taxName: $this->nullableString($row['taxName'] ?? null),
                total: $this->nullableFloat($row['total'] ?? null),
                costCenterName: $this->nullableString($row['costCenterName'] ?? null),
                projectName: $this->nullableString($row['projectName'] ?? null),
            );
        }

        return $lines;
    }

    /**
     * A cost without an account number cannot be attributed, so it is skipped.
     *
     * @return list<PurchaseInvoiceCost>
     */
    private function costsFrom(mixed $costs): array
    {
        if (! is_array($costs)) {
            return [];
        }

        $mapped = [];

        foreach ($costs as $row) {
            $account = is_array($row) ? $this->nullableString($row['coaNo'] ?? null) : null;

            if ($account === null) {
                continue;
            }

            $mapped[] = new PurchaseInvoiceCost(
                accountNumber: $account,
                accountName: $this->nullableString($row['coaDescription'] ?? null),
                amount: $this->nullableFloat($row['amount'] ?? null),
            );
        }

        return $mapped;
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
