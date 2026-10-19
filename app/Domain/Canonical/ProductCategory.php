<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One product category with the accounts it posts to (ESB Core
 * GET /product/category/{categoryID}, [ESB-021]).
 *
 * Depreciation fields only apply to asset categories; a 0 from the ESB is
 * carried as null ("not depreciated"). Depreciation type ids have no
 * documented legend.
 */
readonly class ProductCategory implements JsonSerializable
{
    public function __construct(
        public string $provider,
        public int $id,
        public string $name,
        public ?string $code = null,
        public ?int $typeId = null,
        public ?string $typeName = null,
        public ?string $notes = null,
        public ?bool $active = null,
        public ?string $coaNo = null,
        public ?string $cogsCoaNo = null,
        public ?string $accrualBasisCoaNo = null,
        public ?string $opnameCoaNo = null,
        public ?string $productSalesCoaNo = null,
        public ?string $depreciationCoaNo = null,
        public ?string $depreciationExpenseCoaNo = null,
        public ?int $depreciationMonths = null,
        public ?int $fiscalDepreciationMonths = null,
        public ?int $depreciationTypeId = null,
        public ?int $fiscalDepreciationTypeId = null,
        public ?bool $assetTax = null,
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
