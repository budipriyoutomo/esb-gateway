<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A product as the gateway understands it, independent of who supplied it.
 *
 * Promoted from App\Integrations\ESB\DTOs\ProductSummary (ADR-016). The field
 * set is still the one the ESB documents, because the ESB is the only provider
 * that exists -- ADR-019 records that, and expects revision when a second
 * provider gives a second real example. `bomId`/`bomName` are the likeliest
 * fields to turn out ERP-specific rather than canonical.
 */
readonly class Product implements JsonSerializable
{
    /**
     * @param  string  $provider  Which provider supplied this record. Two
     *                            providers may both use id 41 and mean
     *                            different products, so a canonical record is
     *                            only identifiable together with its origin.
     */
    public function __construct(
        public string $provider,
        public int $id,
        public string $name,
        public string $code,
        public ?int $categoryId = null,
        public ?string $categoryName = null,
        public ?int $subCategoryId = null,
        public ?string $subCategoryName = null,
        public ?int $bomId = null,
        public ?string $bomName = null,
        public ?int $categoryTypeId = null,
        public ?string $categoryTypeName = null,
        public bool $isActive = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'provider' => $this->provider,
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'categoryId' => $this->categoryId,
            'categoryName' => $this->categoryName,
            'subCategoryId' => $this->subCategoryId,
            'subCategoryName' => $this->subCategoryName,
            'bomId' => $this->bomId,
            'bomName' => $this->bomName,
            'categoryTypeId' => $this->categoryTypeId,
            'categoryTypeName' => $this->categoryTypeName,
            'isActive' => $this->isActive,
        ];
    }
}
