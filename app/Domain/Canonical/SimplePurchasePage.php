<?php

namespace App\Domain\Canonical;

/**
 * One page of simple purchases, plus the page state that produced it.
 */
readonly class SimplePurchasePage
{
    /**
     * @param  list<SimplePurchaseSummary>  $simplePurchases
     */
    public function __construct(
        public array $simplePurchases,
        public ?Pagination $pagination = null,
    ) {}
}
