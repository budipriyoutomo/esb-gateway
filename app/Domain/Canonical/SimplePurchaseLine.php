<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One purchased product on a simple purchase.
 *
 * Amounts are display-only, as on SimplePurchaseSummary. Only `taxName` is
 * carried of the tax fields: which of `vat` / `vatValue` / `dppValue` is the
 * tax amount is the same open question as on PurchaseInvoiceLine.
 */
readonly class SimplePurchaseLine implements JsonSerializable
{
    public function __construct(
        public ?int $productDetailId,
        public string $productCode,
        public string $productName,
        public ?float $qty = null,
        public ?string $uomName = null,
        public ?float $price = null,
        public ?float $discount = null,
        public ?float $discountPercent = null,
        public ?string $taxName = null,
        public ?float $total = null,
        public ?string $notes = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
