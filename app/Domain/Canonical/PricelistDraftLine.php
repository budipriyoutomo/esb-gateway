<?php

namespace App\Domain\Canonical;

/**
 * One product (in one unit) and its price on a pricelist draft.
 */
final readonly class PricelistDraftLine
{
    public function __construct(
        public int $productDetailId,
        public int|float $price,
    ) {}
}
