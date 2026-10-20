<?php

namespace App\Domain\Canonical;

/**
 * A product to create, or to replace an existing one with ([ESB-031]).
 * What the form does not edit (BOM, CoreTax code, custom fields, menus) is
 * carried over from the product as the ERP has it on an update.
 */
final readonly class ProductDraft
{
    /**
     * @param  list<ProductUnitDraft>  $units
     */
    public function __construct(
        public string $name,
        public int $categoryId,
        public int $subCategoryId,
        public ?string $code = null,
        public ?string $notes = null,
        public bool $purchasable = false,
        public bool $saleable = false,
        public bool $requestable = false,
        public bool $vat = false,
        public bool $luxury = false,
        public int|float|null $receiptTolerance = null,
        public array $units = [],
    ) {}
}
