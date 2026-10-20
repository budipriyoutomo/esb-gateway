<?php

namespace App\Domain\Canonical;

/**
 * A product category to create, or to replace an existing one with
 * ([ESB-030]). Account fields hold COA numbers; depreciation only applies to
 * asset categories. Null is "none".
 */
final readonly class ProductCategoryDraft
{
    public function __construct(
        public string $name,
        public int $typeId,
        public ?string $code = null,
        public ?string $notes = null,
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
        public bool $assetTax = false,
    ) {}
}
