<?php

namespace App\Integrations\ESB\DTOs;

use App\Integrations\ESB\Responses\EsbPagination;

/**
 * The outcome of GET /product/list: our rows plus our own pagination state.
 *
 * The ESB's absolute prev/next URLs are deliberately dropped -- see
 * EsbPagination.
 */
readonly class ProductListResult
{
    /**
     * @param  list<ProductSummary>  $products
     */
    public function __construct(
        public array $products,
        public ?EsbPagination $pagination,
    ) {}
}
