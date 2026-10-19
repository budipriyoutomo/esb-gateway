<?php

namespace App\Domain\Canonical;

/**
 * One page of product category summaries, plus the page state that produced it.
 */
readonly class ProductCategoryPage
{
    /**
     * @param  list<ProductCategorySummary>  $categories
     */
    public function __construct(
        public array $categories,
        public ?Pagination $pagination = null,
    ) {}
}
