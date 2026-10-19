<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A product as this supplier codes it ([ESB-024]).
 */
readonly class SupplierProduct implements JsonSerializable
{
    public function __construct(
        public ?int $productDetailId,
        public ?string $productName,
        public ?string $supplierProductCode,
        public ?string $uomName,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
