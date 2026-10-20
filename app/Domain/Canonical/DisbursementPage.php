<?php

namespace App\Domain\Canonical;

/**
 * One page of the disbursement list.
 */
final readonly class DisbursementPage
{
    /**
     * @param  list<DisbursementSummary>  $disbursements
     */
    public function __construct(
        public array $disbursements,
        public ?Pagination $pagination,
    ) {}
}
