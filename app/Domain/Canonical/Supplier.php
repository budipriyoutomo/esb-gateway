<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One supplier (ESB Core GET /supplier/{supplierID}, [ESB-024]).
 */
readonly class Supplier implements JsonSerializable
{
    /**
     * @param  list<SupplierContact>  $contacts
     * @param  list<SupplierBankAccount>  $bankAccounts
     * @param  list<SupplierProduct>  $products
     */
    public function __construct(
        public string $provider,
        public int $id,
        public string $name,
        public ?string $code = null,
        public ?int $categoryId = null,
        public ?string $categoryName = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $country = null,
        public ?string $zipCode = null,
        public ?string $phone1 = null,
        public ?string $phone2 = null,
        public ?string $fax = null,
        public ?string $npwp = null,
        public ?string $notes = null,
        public ?int $creditDays = null,
        public ?string $payableCoaNo = null,
        public ?string $advancePurchaseCoaNo = null,
        public ?int $paymentMethodId = null,
        public ?bool $vatSubject = null,
        public ?bool $lockVat = null,
        public ?bool $invoiceOnReceipt = null,
        public ?bool $usedInTransactions = null,
        public ?bool $active = null,
        public array $contacts = [],
        public array $bankAccounts = [],
        public array $products = [],
        public ?string $createdBy = null,
        public ?string $createdAt = null,
        public ?string $editedBy = null,
        public ?string $editedAt = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
