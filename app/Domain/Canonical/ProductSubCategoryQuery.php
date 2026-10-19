<?php

namespace App\Domain\Canonical;

/**
 * Filters for a ProductSubCategorySource list. Values are already validated;
 * null means "omit this filter". The vendor documents no category filter.
 */
readonly class ProductSubCategoryQuery
{
    public function __construct(
        public int $page = 1,
        public int $limit = 20,
        /** Free text on sub-category name or notes (the ESB's indexSearch). */
        public ?string $search = null,
        public ?bool $activeOnly = null,
    ) {}
}
