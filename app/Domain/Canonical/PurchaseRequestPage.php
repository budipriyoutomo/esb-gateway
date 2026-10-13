<?php

namespace App\Domain\Canonical;

/**
 * One page of purchase requests, plus the page state that produced it.
 */
readonly class PurchaseRequestPage
{
    /**
     * @param  list<PurchaseRequestSummary>  $purchaseRequests
     */
    public function __construct(
        public array $purchaseRequests,
        public ?Pagination $pagination = null,
    ) {}
}
