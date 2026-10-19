<?php

namespace App\Domain\Canonical;

/**
 * One page of product sub-category summaries, plus the page state that
 * produced it.
 */
readonly class ProductSubCategoryPage
{
    /**
     * @param  list<ProductSubCategorySummary>  $subCategories
     */
    public function __construct(
        public array $subCategories,
        public ?Pagination $pagination = null,
    ) {}
}
