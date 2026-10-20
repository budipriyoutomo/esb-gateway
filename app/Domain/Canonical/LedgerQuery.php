<?php

namespace App\Domain\Canonical;

/**
 * The general ledger of one account over a period ([ESB-042]). The other
 * filters are optional; null is "no filter".
 */
final readonly class LedgerQuery
{
    public const DEFAULT_LIMIT = 20;

    public const MAX_LIMIT = 100;

    /** The longest period the gateway asks for: the entries come back unpaged. */
    public const MAX_DAYS = 366;

    public function __construct(
        public string $coaNo,
        public string $dateFrom,
        public string $dateTo,
        public int $page = 1,
        public int $limit = self::DEFAULT_LIMIT,
        public ?int $branchId = null,
        public ?LedgerSide $side = null,
        public ?string $reference = null,
        public ?string $notes = null,
        public ?string $transactionType = null,
    ) {}
}
