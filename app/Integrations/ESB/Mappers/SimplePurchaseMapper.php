<?php

namespace App\Integrations\ESB\Mappers;

use App\Domain\Canonical\SimplePurchase;
use App\Domain\Canonical\SimplePurchaseCost;
use App\Domain\Canonical\SimplePurchaseLine;
use App\Domain\Canonical\SimplePurchasePayment;
use App\Domain\Canonical\SimplePurchaseSummary;
use App\Integrations\ESB\Clients\SimplePurchaseClient;

/**
 * Maps the ESB's simple purchase list rows and detail result onto the
 * canonical shapes.
 *
 * ESB responses are untrusted input (docs/10-security.md), so every field is
 * read defensively. Empty strings become null, and a reference id of 0 means
 * "none" -- the vendor's placeholders use both for unset values.
 */
class SimplePurchaseMapper
{
    /**
     * @param  array<string, mixed>  $row
     */
    public function summaryFromRow(array $row): SimplePurchaseSummary
    {
        return new SimplePurchaseSummary(
            provider: SimplePurchaseClient::PROVIDER,
            number: $this->stringOr($row['cashPurchaseNum'] ?? null, ''),
            supplierInvoiceNumber: $this->nullableString($row['supplierInvoiceNum'] ?? null),
            date: $this->nullableString($row['cashPurchaseDate'] ?? null),
            branchId: $this->nullableId($row['branchID'] ?? null),
            branchName: $this->nullableString($row['branchName'] ?? null),
            supplierId: $this->nullableId($row['supplierID'] ?? null),
            supplierName: $this->nullableString($row['supplierName'] ?? null),
            currencySign: $this->nullableString($row['currencySign'] ?? null),
            total: $this->nullableFloat($row['cashPurchaseTotal'] ?? null),
            statusId: $this->nullableInt($row['statusID'] ?? null),
            statusName: $this->nullableString($row['statusName'] ?? null),
            createdByName: $this->nullableString($row['createdBy'] ?? null),
            notes: $this->nullableString($row['additionalInfo'] ?? null),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<SimplePurchaseSummary>
     */
    public function summariesFromRows(array $rows): array
    {
        return array_map(fn (array $row): SimplePurchaseSummary => $this->summaryFromRow($row), $rows);
    }

    /**
     * @param  array<mixed>  $result
     */
    public function fromDetail(array $result): SimplePurchase
    {
        return new SimplePurchase(
            provider: SimplePurchaseClient::PROVIDER,
            number: $this->stringOr($result['cashPurchaseNum'] ?? null, ''),
            supplierInvoiceNumber: $this->nullableString($result['supplierInvoiceNum'] ?? null),
            date: $this->nullableString($result['cashPurchaseDate'] ?? null),
            dueDate: $this->nullableString($result['cashPurchaseDueDate'] ?? null),
            purchaseTypeName: $this->nullableString($result['purchaseTypeName'] ?? null),
            branchId: $this->nullableId($result['branchID'] ?? null),
            branchName: $this->nullableString($result['branchName'] ?? null),
            locationName: $this->nullableString($result['locationName'] ?? null),
            supplierId: $this->nullableId($result['supplierID'] ?? null),
            supplierName: $this->nullableString($result['supplierName'] ?? null),
            supplierPhone: $this->nullableString($result['supplierPhone1'] ?? null),
            contactName: $this->nullableString($result['picName'] ?? null),
            paymentMethodName: $this->nullableString($result['paymentName'] ?? null),
            currencyId: $this->nullableId($result['currencyID'] ?? null),
            currencyName: $this->nullableString($result['currencyName'] ?? null),
            currencySign: $this->nullableString($result['currencySign'] ?? null),
            rate: $this->nullableFloat($result['rate'] ?? null),
            costCenterName: $this->nullableString($result['costCenterName'] ?? null),
            projectName: $this->nullableString($result['projectName'] ?? null),
            costTotal: $this->nullableFloat($result['costTotal'] ?? null),
            total: $this->nullableFloat($result['cashPurchaseTotal'] ?? null),
            statusId: $this->nullableInt($result['statusID'] ?? null),
            statusName: $this->nullableString($result['statusName'] ?? null),
            notes: $this->nullableString($result['additionalInfo'] ?? null),
            createdByName: $this->personName($result['creator'] ?? null, $result['createdBy'] ?? null),
            createdAt: $this->nullableString($result['createdDate'] ?? null),
            editedByName: $this->personName($result['editor'] ?? null, $result['editedBy'] ?? null),
            editedAt: $this->nullableString($result['editedDate'] ?? null),
            authorizedByName: $this->personName($result['authorizer'] ?? null, $result['authorizedBy'] ?? null),
            authorizedAt: $this->nullableString($result['authorizedDate'] ?? null),
            lines: $this->linesFrom($result['simplePurchaseDetails'] ?? null),
            costs: $this->costsFrom($result['simplePurchaseCosts'] ?? null),
            payments: $this->paymentsFrom($result['purchasePayments'] ?? null),
        );
    }

    /**
     * The detail result's `creator` / `editor` / `authorizer` objects, with
     * the plain username field as the fallback.
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
     * @return list<SimplePurchaseLine>
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

            $lines[] = new SimplePurchaseLine(
                productDetailId: $this->nullableId($row['productDetailID'] ?? null),
                productCode: $this->stringOr($row['productCode'] ?? null, ''),
                productName: $this->stringOr($row['productName'] ?? null, ''),
                qty: $this->nullableFloat($row['qty'] ?? null),
                uomName: $this->nullableString($row['uomName'] ?? null),
                price: $this->nullableFloat($row['price'] ?? null),
                discount: $this->nullableFloat($row['discount'] ?? null),
                discountPercent: $this->nullableFloat($row['discountPercent'] ?? null),
                taxName: $this->nullableString($row['taxName'] ?? null),
                total: $this->nullableFloat($row['total'] ?? null),
                notes: $this->nullableString($row['notes'] ?? null),
            );
        }

        return $lines;
    }

    /**
     * A cost without an account number cannot be attributed -- the vendor's
     * placeholder is one -- so it is skipped.
     *
     * @return list<SimplePurchaseCost>
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

            $mapped[] = new SimplePurchaseCost(
                accountNumber: $account,
                description: $this->nullableString($row['description'] ?? null),
                amount: $this->nullableFloat($row['amount'] ?? null),
                notes: $this->nullableString($row['notes'] ?? null),
            );
        }

        return $mapped;
    }

    /**
     * A payment without a number cannot be identified, so it is skipped.
     *
     * @return list<SimplePurchasePayment>
     */
    private function paymentsFrom(mixed $payments): array
    {
        if (! is_array($payments)) {
            return [];
        }

        $mapped = [];

        foreach ($payments as $row) {
            $number = is_array($row) ? $this->nullableString($row['purchasePaymentNum'] ?? null) : null;

            if ($number === null) {
                continue;
            }

            $mapped[] = new SimplePurchasePayment(
                number: $number,
                date: $this->nullableString($row['paymentDate'] ?? null),
                amount: $this->nullableFloat($row['paymentTotal'] ?? null),
                accountName: $this->nullableString($row['bankAccountName'] ?? null),
                statusName: $this->nullableString($row['statusName'] ?? null),
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
