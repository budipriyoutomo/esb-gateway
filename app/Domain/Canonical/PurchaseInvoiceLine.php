<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One invoiced product on a purchase invoice.
 *
 * `referenceNumber` / `referenceDate` are the document the line was invoiced
 * from (the vendor's `refNum` / `transDate`, e.g. a goods receipt), and
 * `purchaseOrderNumber` the order behind it. Amounts are display-only, as on
 * PurchaseInvoiceSummary.
 *
 * Not carried: the vendor's `vat`, `vatValue`, `vatTotal`, and `dppValue`.
 * The create schema maps `vatTotal` onto a column named `vatValue` and
 * `vatValue` onto one named `vat`, so which figure is which is TBD with the
 * ESB team. Showing the wrong one as "tax" would be worse than showing none.
 */
readonly class PurchaseInvoiceLine implements JsonSerializable
{
    public function __construct(
        public ?int $productDetailId,
        public string $productCode,
        public string $productName,
        public ?string $referenceNumber = null,
        public ?string $referenceDate = null,
        public ?string $purchaseOrderNumber = null,
        public ?float $qty = null,
        public ?string $uomName = null,
        public ?float $price = null,
        public ?float $discount = null,
        public ?float $discountPercent = null,
        public ?string $taxName = null,
        public ?float $total = null,
        public ?string $costCenterName = null,
        public ?string $projectName = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
