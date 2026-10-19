<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One ordered product on a purchase order.
 *
 * `requestedQty` is the vendor's `requestQty`: the quantity asked for on the
 * purchase request(s) the order was raised from. Amounts are display-only, as
 * on PurchaseOrderSummary.
 *
 * Not carried: the vendor's `vat`, `vatValue` and `dppValue`. The same naming
 * question as on PurchaseInvoiceLine is open with the ESB team, so no figure
 * is shown as "tax". Also not carried: the pricelist, last-price, stock and
 * deviation fields, which no screen needs yet.
 */
readonly class PurchaseOrderLine implements JsonSerializable
{
    public function __construct(
        public ?int $productDetailId,
        public string $productCode,
        public string $productName,
        public ?float $qty = null,
        public ?float $requestedQty = null,
        public ?string $uomName = null,
        public ?float $price = null,
        public ?float $discount = null,
        public ?float $discountPercent = null,
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
