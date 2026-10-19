<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One product of a document template, in a unit ([ESB-016]).
 *
 * The vendor's stockQty and availableQty are left out: the detail is read
 * without a branch, so they have no stated location. mode and
 * requestProcessID are undocumented beyond their names (TBD).
 */
readonly class DocumentTemplateLine implements JsonSerializable
{
    public function __construct(
        public ?int $lineId,
        public ?int $productDetailId,
        public ?string $productCode,
        public string $productName,
        public ?string $name = null,
        public ?string $uomName = null,
        public ?string $categoryName = null,
        public ?int $purposeId = null,
        public ?float $lastCost = null,
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
