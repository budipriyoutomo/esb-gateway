<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One material of a bill of material: a product in a unit, with its quantity
 * and the substitutes the ERP allows for it ([ESB-014]).
 */
readonly class BillOfMaterialLine implements JsonSerializable
{
    /**
     * @param  list<BillOfMaterialSubstitute>  $substitutes
     * @param  list<string>  $manufacturingSubstitutes  Product names only; the vendor sends no more.
     */
    public function __construct(
        public ?int $lineId,
        public int $productDetailId,
        public ?string $productCode,
        public string $productName,
        public ?string $uomName = null,
        public ?float $qty = null,
        public ?float $uomQty = null,
        public ?float $conversionQty = null,
        public ?float $lastCost = null,
        public ?float $price = null,
        public ?string $printGroup = null,
        public ?float $tolerancePercent = null,
        public ?float $yieldPercent = null,
        public ?float $weightFactor = null,
        public array $substitutes = [],
        public array $manufacturingSubstitutes = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
