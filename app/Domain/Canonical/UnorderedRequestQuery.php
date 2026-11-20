<?php

namespace App\Domain\Canonical;

/**
 * What a RequestMonitoringSource is asked for ([ESB-053]). The vendor
 * requires at least one branch. Category, product, cost center and project
 * filters exist on the vendor's side; no screen asks for them yet.
 */
readonly class UnorderedRequestQuery
{
    /**
     * @param  non-empty-list<int>  $branchIds
     */
    public function __construct(
        public array $branchIds,
        public ?string $requiredDateFrom = null,
        public ?string $requiredDateTo = null,
        public ?string $purchaseRequestNumber = null,
    ) {}
}
