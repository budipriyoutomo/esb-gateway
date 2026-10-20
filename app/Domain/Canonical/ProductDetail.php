<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One product with its units (ESB Core GET /product/{productID},
 * [ESB-026]). The list row stays App\Domain\Canonical\Product.
 */
readonly class ProductDetail implements JsonSerializable
{
    /**
     * @param  list<ProductUnit>  $units
     * @param  array<string, string|null>  $customFields
     */
    public function __construct(
        public string $provider,
        public int $id,
        public string $name,
        public ?string $code = null,
        public ?int $categoryId = null,
        public ?string $categoryName = null,
        public ?int $subCategoryId = null,
        public ?string $subCategoryName = null,
        public ?int $bomId = null,
        public ?string $bomName = null,
        public ?bool $purchasable = null,
        public ?bool $saleable = null,
        public ?bool $requestable = null,
        public ?bool $vat = null,
        public ?bool $luxury = null,
        /** Percent over the ordered quantity a receipt may accept (assumed unit). */
        public ?float $receiptTolerance = null,
        public ?string $coretaxCode = null,
        public ?string $notes = null,
        public ?bool $active = null,
        public array $units = [],
        public ?int $coretaxCodeId = null,
        public array $customFields = [],
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
