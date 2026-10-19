<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A product the ERP accepts in place of a bill-of-material line ([ESB-014]).
 */
readonly class BillOfMaterialSubstitute implements JsonSerializable
{
    public function __construct(
        public int $productDetailId,
        public ?string $productCode,
        public ?string $productName,
        public ?string $uomName = null,
        public ?int $order = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
