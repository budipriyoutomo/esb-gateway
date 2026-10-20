<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A price a product was recently bought at, from a purchase invoice
 * ([ESB-032], GET /pricelist/history/purchase-invoice). Display only.
 */
readonly class PurchasePrice implements JsonSerializable
{
    public function __construct(
        public string $invoiceNumber,
        public ?string $invoiceDate,
        public int|float|null $price,
        public ?int $supplierId,
        public ?string $supplierName,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
