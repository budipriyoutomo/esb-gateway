<?php

namespace App\Integrations\ESB\Mappers;

use App\Domain\Canonical\Supplier;
use App\Domain\Canonical\SupplierBankAccount;
use App\Domain\Canonical\SupplierContact;
use App\Domain\Canonical\SupplierProduct;
use App\Domain\Canonical\SupplierSummary;
use App\Integrations\ESB\Clients\SupplierClient;
use App\Integrations\ESB\Mappers\Concerns\ReadsEsbValues;

/**
 * Maps ESB Core suppliers onto the canonical Supplier ([ESB-024]).
 *
 * Bank account numbers are reduced to their last four digits here, so the
 * full number never leaves the adapter.
 */
class SupplierMapper
{
    use ReadsEsbValues;

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<SupplierSummary>
     */
    public function summariesFromRows(array $rows): array
    {
        return array_map(fn (array $row): SupplierSummary => $this->summaryFromRow($row), $rows);
    }

    /**
     * @param  array<mixed>  $row
     */
    public function summaryFromRow(array $row): SupplierSummary
    {
        return new SupplierSummary(
            provider: SupplierClient::PROVIDER,
            id: $this->nullableId($row['supplierID'] ?? null),
            name: $this->stringOr($row['supplierName'] ?? null, ''),
            code: $this->nullableString($row['supplierCode'] ?? null),
            categoryId: $this->nullableId($row['supplierCategoryID'] ?? null),
            categoryName: $this->nullableString($row['category'] ?? null),
            address: $this->nullableString($row['address'] ?? null),
            contactPerson: $this->nullableString($row['contactPerson'] ?? null),
            cellPhone: $this->nullableString($row['cellPhone'] ?? null),
            creditDays: $this->creditDays($row['dueDate'] ?? null),
            vatSubject: $this->flag($row['vatSubject'] ?? null),
            active: $this->flag($row['flagActive'] ?? null),
        );
    }

    /**
     * The detail result, or null when it carries no id.
     *
     * @param  array<mixed>  $result
     */
    public function fromDetail(array $result): ?Supplier
    {
        $id = $this->nullableId($result['supplierID'] ?? null);

        if ($id === null) {
            return null;
        }

        return new Supplier(
            provider: SupplierClient::PROVIDER,
            id: $id,
            name: $this->stringOr($result['supplierName'] ?? null, ''),
            code: $this->nullableString($result['supplierCode'] ?? null),
            categoryId: $this->nullableId($result['supplierCategoryID'] ?? null),
            categoryName: $this->nullableString($result['supplierCategoryName'] ?? null),
            address: $this->nullableString($result['address'] ?? null),
            city: $this->nullableString($result['city'] ?? null),
            state: $this->nullableString($result['state'] ?? null),
            country: $this->nullableString($result['country'] ?? null),
            zipCode: $this->nullableString($result['zipCode'] ?? null),
            phone1: $this->nullableString($result['phone1'] ?? null),
            phone2: $this->nullableString($result['phone2'] ?? null),
            fax: $this->nullableString($result['fax'] ?? null),
            npwp: $this->nullableString($result['npwp'] ?? null),
            notes: $this->nullableString($result['notes'] ?? null),
            creditDays: $this->creditDays($result['dueDate'] ?? null),
            payableCoaNo: $this->nullableString($result['payableCoaNo'] ?? null),
            advancePurchaseCoaNo: $this->nullableString($result['advancePurchaseCoaNo'] ?? null),
            paymentMethodId: $this->nullableId($result['paymentID'] ?? null),
            vatSubject: $this->flag($result['vatSubject'] ?? null),
            lockVat: $this->flag($result['lockVAT'] ?? null),
            invoiceOnReceipt: $this->flag($result['flagInvoiceOnReceipt'] ?? null),
            usedInTransactions: $this->flag($result['isAnyTransaction'] ?? null),
            active: $this->flag($result['flagActive'] ?? null),
            contacts: $this->each($result['supplierPic'] ?? null, fn (array $pic) => new SupplierContact(
                id: $this->nullableId($pic['picSupplierID'] ?? null),
                name: $this->stringOr($pic['picName'] ?? null, ''),
                cellPhone: $this->nullableString($pic['cellPhone'] ?? null),
                email: $this->nullableString($pic['email'] ?? null),
                isDefault: $this->flag($pic['flagDefault'] ?? null),
                sendsPurchaseOrderEmail: $this->flag($pic['flagSendingPOEmail'] ?? null),
            )),
            bankAccounts: $this->each($result['supplierBank'] ?? null, fn (array $bank) => new SupplierBankAccount(
                bankId: $this->nullableId($bank['bankID'] ?? null),
                bankName: $this->nullableString($bank['bankName'] ?? null),
                accountName: $this->nullableString($bank['bankAccountName'] ?? null),
                maskedAccountNumber: $this->mask($bank['bankAccountNumber'] ?? null),
            )),
            // Counted only: the files live in the ERP's storage, and whether the
            // browser may open those URLs is undecided (TODO-modules §2.9).
            attachmentCount: $this->attachmentCount($result['imageAttachments'] ?? null),
            products: $this->each($result['supplierProduct'] ?? null, fn (array $product) => new SupplierProduct(
                productDetailId: $this->nullableId($product['productDetailID'] ?? null),
                productName: $this->nullableString($product['productName'] ?? null),
                supplierProductCode: $this->nullableString($product['externalProductCode'] ?? null),
                uomName: $this->nullableString($product['uomName'] ?? null),
            )),
            createdBy: $this->nullableString($result['createdBy'] ?? null),
            createdAt: $this->nullableString($result['createdDate'] ?? null),
            editedBy: $this->nullableString($result['editedBy'] ?? null),
            editedAt: $this->nullableString($result['editedDate'] ?? null),
        );
    }

    /**
     * Credit terms in days; 0 is "cash", a real value, so only non-numbers
     * and negatives are none.
     */
    private function creditDays(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value >= 0 ? (int) $value : null;
    }

    /**
     * Keep only the last four digits: "•••• 6789". A number too short to
     * mask safely is hidden entirely.
     */
    private function mask(mixed $number): ?string
    {
        $number = $this->nullableString(is_string($number) ? trim($number) : null);

        if ($number === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $number) ?? '';

        return strlen($digits) >= 8 ? '•••• '.substr($digits, -4) : '••••';
    }

    /**
     * @template T
     *
     * @param  callable(array<mixed>): T  $map
     * @return list<T>
     */
    private function each(mixed $rows, callable $map): array
    {
        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_map($map, array_filter($rows, 'is_array')));
    }

    private function attachmentCount(mixed $attachments): int
    {
        if (! is_array($attachments)) {
            return 0;
        }

        return count(array_filter($attachments, static fn (mixed $url): bool => is_string($url) && trim($url) !== ''));
    }
}
