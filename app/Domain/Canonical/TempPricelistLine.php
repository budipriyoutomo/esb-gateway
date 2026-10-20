<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One proposed price on a temporary pricelist ([ESB-027]).
 */
readonly class TempPricelistLine implements JsonSerializable
{
    public function __construct(
        public ?int $productDetailId,
        public ?string $productCode,
        public ?string $productName,
        public ?string $unitName,
        public ?float $price,
        /** The price before this proposal, if any. */
        public ?float $lastPrice,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
