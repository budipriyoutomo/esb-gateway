<?php

namespace App\Domain\Canonical;

/**
 * One page of the general ledger summary.
 */
final readonly class LedgerSummaryPage
{
    /**
     * @param  list<LedgerSummaryRow>  $rows
     */
    public function __construct(
        public array $rows,
        public ?Pagination $pagination,
    ) {}
}
