<?php

namespace App\Domain\Canonical;

/**
 * One page of temporary pricelists, plus the page state that produced it.
 */
readonly class TempPricelistPage
{
    /**
     * @param  list<TempPricelistSummary>  $pricelists
     */
    public function __construct(
        public array $pricelists,
        public ?Pagination $pagination = null,
    ) {}
}
