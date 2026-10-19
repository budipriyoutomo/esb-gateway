<?php

namespace App\Domain\Canonical;

/**
 * One product and its active units, as GET /product/{productID} returns it
 * ([ESB-016], ADR-023). Each unit is a product detail a template line can
 * point at.
 */
readonly class ProductUnits
{
    /**
     * @param  list<ProductDetailOption>  $units  Active units only.
     */
    public function __construct(
        public int $productId,
        public ?string $productCode,
        public string $productName,
        public ?bool $active,
        public array $units,
    ) {}
}
