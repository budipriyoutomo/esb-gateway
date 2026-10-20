<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One unit a product is handled in -- the ERP's "product detail"
 * ([ESB-026]). Lines on purchase and inventory documents point at its
 * productDetailId.
 */
readonly class ProductUnit implements JsonSerializable
{
    public function __construct(
        public int $productDetailId,
        public ?int $uomId,
        public ?string $uomName,
        /** How many base units one of this unit holds. */
        public ?float $qty = null,
        public ?bool $isBase = null,
        public ?bool $purchasable = null,
        public ?bool $saleable = null,
        public ?bool $stocked = null,
        public ?bool $transferable = null,
        public ?string $sku = null,
        /** Display only: the ERP is the authority on prices. */
        public ?float $basePrice = null,
        public ?float $weight = null,
        public ?string $menuName = null,
        public ?int $menuId = null,
        public ?float $cubication = null,
        public ?bool $active = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
