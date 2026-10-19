<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A product in one unit, as a picker offers it: one productDetails[] row of
 * ESB Core GET /product/{productID}, with its product's code and name
 * ([ESB-016]). Only what a picker needs.
 */
readonly class ProductDetailOption implements JsonSerializable
{
    public function __construct(
        public int $productDetailId,
        public ?string $productCode,
        public string $productName,
        public ?string $uomName = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
