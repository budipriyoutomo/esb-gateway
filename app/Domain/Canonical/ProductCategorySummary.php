<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A product category as a list row (ESB Core GET /product/category,
 * [ESB-021]).
 */
readonly class ProductCategorySummary implements JsonSerializable
{
    public function __construct(
        public string $provider,
        public ?int $id,
        public string $name,
        public ?int $typeId = null,
        public ?string $typeName = null,
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
