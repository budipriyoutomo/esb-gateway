<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A bill of material as a list row: what it is and what it produces, without
 * its materials. The field set is the ESB Core GET /product/bom row's
 * ([ESB-014]).
 */
readonly class BillOfMaterialSummary implements JsonSerializable
{
    public function __construct(
        public string $provider,
        public ?int $id,
        public ?string $code,
        public string $name,
        public ?int $typeId = null,
        public ?string $typeName = null,
        public ?string $productName = null,
        public ?string $uomName = null,
        public ?string $notes = null,
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
