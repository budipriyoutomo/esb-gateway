<?php

namespace App\Domain\Canonical;

/**
 * One page of canonical products, plus the page state that produced it.
 *
 * Pagination is nullable because a provider is not obliged to report it; a
 * caller must handle its absence rather than assume a single page.
 */
readonly class ProductPage
{
    /**
     * @param  list<Product>  $products
     */
    public function __construct(
        public array $products,
        public ?Pagination $pagination = null,
    ) {}
}
