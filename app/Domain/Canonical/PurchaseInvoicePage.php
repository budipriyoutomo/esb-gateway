<?php

namespace App\Domain\Canonical;

/**
 * One page of purchase invoices, plus the page state that produced it.
 */
readonly class PurchaseInvoicePage
{
    /**
     * @param  list<PurchaseInvoiceSummary>  $purchaseInvoices
     */
    public function __construct(
        public array $purchaseInvoices,
        public ?Pagination $pagination = null,
    ) {}
}
