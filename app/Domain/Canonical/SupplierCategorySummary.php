<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A supplier category as a list row (ESB Core GET /supplier/category/list,
 * [ESB-023]).
 */
readonly class SupplierCategorySummary implements JsonSerializable
{
    public function __construct(
        public string $provider,
        public ?int $id,
        public string $name,
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
