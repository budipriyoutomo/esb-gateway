<?php

namespace App\Domain\Canonical;

/**
 * The general ledger summary for a period ([ESB-042]); the account and the
 * branch narrow it.
 */
final readonly class LedgerSummaryQuery
{
    public const DEFAULT_LIMIT = 20;

    public const MAX_LIMIT = 100;

    public function __construct(
        public string $dateFrom,
        public string $dateTo,
        public int $page = 1,
        public int $limit = self::DEFAULT_LIMIT,
        public ?string $accountNo = null,
        public ?int $branchId = null,
    ) {}
}
