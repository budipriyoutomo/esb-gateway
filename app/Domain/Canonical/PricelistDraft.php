<?php

namespace App\Domain\Canonical;

/**
 * A supplier pricelist to propose, or to replace a proposed (temporary) one
 * with ([ESB-031]). Dates are YYYY-MM-DD; no branches means every branch.
 */
final readonly class PricelistDraft
{
    /**
     * @param  list<int>  $branchIds
     * @param  list<PricelistDraftLine>  $lines
     */
    public function __construct(
        public int $supplierId,
        public int $currencyId,
        public string $priceDate,
        public string $expireDate,
        public array $branchIds,
        public array $lines,
    ) {}
}
