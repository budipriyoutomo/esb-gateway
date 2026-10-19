<?php

namespace App\Domain\Canonical;

/**
 * One page of purchase orders, plus the page state that produced it.
 */
readonly class PurchaseOrderPage
{
    /**
     * @param  list<PurchaseOrderSummary>  $purchaseOrders
     */
    public function __construct(
        public array $purchaseOrders,
        public ?Pagination $pagination = null,
    ) {}
}
