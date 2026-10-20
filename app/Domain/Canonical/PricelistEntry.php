<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One active supplier price for one product unit (ESB Core GET /pricelist,
 * [ESB-027]). Display only: the ERP is the authority on prices.
 */
readonly class PricelistEntry implements JsonSerializable
{
    public function __construct(
        public string $provider,
        /** The vendor's "pricelist detail row ID"; not known to open a detail. */
        public ?int $rowId,
        public ?string $pricelistNumber,
        public ?int $productDetailId,
        public ?string $productCode,
        public ?string $productName,
        public ?string $unit,
        public ?int $supplierId,
        public ?string $supplierName,
        public ?string $currencyName,
        public ?float $price,
        public ?string $priceDate,
        public ?string $expireDate,
        public BranchScope $branches,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
