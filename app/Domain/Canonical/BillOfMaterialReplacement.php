<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A product swapped for another when this bill of material is used for a
 * particular menu ([ESB-014]).
 */
readonly class BillOfMaterialReplacement implements JsonSerializable
{
    public function __construct(
        public ?string $menuCode,
        public ?string $menuName,
        public ?string $productCode,
        public ?string $productName,
        public ?string $replacementProductCode,
        public ?string $replacementProductName,
        public ?string $uomName = null,
        public ?float $qty = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
