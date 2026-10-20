<?php

namespace App\Domain\Canonical;

/**
 * One unit of a product draft. productDetailId is set for a unit the product
 * already has, null for a new one.
 */
final readonly class ProductUnitDraft
{
    public function __construct(
        public int $uomId,
        public int|float $qty,
        public bool $isBase,
        public bool $purchasable,
        public bool $saleable,
        public bool $stocked,
        public bool $transferable,
        public ?string $sku,
        public int|float|null $basePrice,
        public int|float|null $weight,
        public bool $active,
        public ?int $productDetailId = null,
    ) {}
}
