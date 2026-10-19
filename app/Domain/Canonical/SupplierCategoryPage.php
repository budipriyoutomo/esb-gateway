<?php

namespace App\Domain\Canonical;

/**
 * One page of supplier category summaries, plus the page state that
 * produced it.
 */
readonly class SupplierCategoryPage
{
    /**
     * @param  list<SupplierCategorySummary>  $categories
     */
    public function __construct(
        public array $categories,
        public ?Pagination $pagination = null,
    ) {}
}
