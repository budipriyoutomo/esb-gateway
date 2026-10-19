<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A product sub-category as a list row (ESB Core GET /product/sub-category,
 * [ESB-022]). Rows carry no parent category.
 */
readonly class ProductSubCategorySummary implements JsonSerializable
{
    public function __construct(
        public string $provider,
        public ?int $id,
        public string $name,
        /** Days without movement before stock counts as dead. */
        public ?int $deadStockDays = null,
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
