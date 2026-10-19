<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One supplier category (ESB Core GET /supplier/category/{id}, [ESB-023]).
 */
readonly class SupplierCategory implements JsonSerializable
{
    public function __construct(
        public string $provider,
        public int $id,
        public string $name,
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
