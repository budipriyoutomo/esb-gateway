<?php

namespace App\Domain\Canonical;

/**
 * One page of bill-of-material summaries, plus the page state that produced it.
 */
readonly class BillOfMaterialPage
{
    /**
     * @param  list<BillOfMaterialSummary>  $boms
     */
    public function __construct(
        public array $boms,
        public ?Pagination $pagination = null,
    ) {}
}
