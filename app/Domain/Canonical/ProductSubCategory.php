<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One product sub-category (ESB Core GET /product/sub-category/{id},
 * [ESB-022]).
 */
readonly class ProductSubCategory implements JsonSerializable
{
    public function __construct(
        public string $provider,
        public int $id,
        public string $name,
        public ?int $deadStockDays = null,
        public ?string $notes = null,
        public ?bool $active = null,
        public ?string $createdBy = null,
        public ?string $createdAt = null,
        public ?string $editedBy = null,
        public ?string $editedAt = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
