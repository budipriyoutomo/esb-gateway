<?php

namespace App\Domain\Canonical;

/**
 * One page of active pricelist entries, plus the page state that produced it.
 */
readonly class PricelistPage
{
    /**
     * @param  list<PricelistEntry>  $entries
     */
    public function __construct(
        public array $entries,
        public ?Pagination $pagination = null,
    ) {}
}
