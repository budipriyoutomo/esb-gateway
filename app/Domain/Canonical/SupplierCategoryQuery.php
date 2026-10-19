<?php

namespace App\Domain\Canonical;

/**
 * Filters for a SupplierCategorySource list. Values are already validated;
 * null means "omit this filter".
 */
readonly class SupplierCategoryQuery
{
    public function __construct(
        public int $page = 1,
        public int $limit = 20,
        /** Matched against the name (the ESB's supplierCategoryName); no free-text search is documented. */
        public ?string $search = null,
        public ?bool $activeOnly = null,
    ) {}
}
