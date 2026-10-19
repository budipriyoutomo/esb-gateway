<?php

namespace App\Domain\Canonical;

/**
 * One page of supplier summaries, plus the page state that produced it.
 */
readonly class SupplierPage
{
    /**
     * @param  list<SupplierSummary>  $suppliers
     */
    public function __construct(
        public array $suppliers,
        public ?Pagination $pagination = null,
    ) {}
}
