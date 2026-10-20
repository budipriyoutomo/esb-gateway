<?php

namespace App\Domain\Canonical;

/**
 * Filters for the general journal ([ESB-041]). Null is "no filter". The
 * vendor's array filters (branch, account, cost center, project) are not
 * offered: their format is undocumented.
 */
final readonly class GeneralJournalQuery
{
    public const DEFAULT_LIMIT = 20;

    public const MAX_LIMIT = 100;

    public function __construct(
        public int $page = 1,
        public int $limit = self::DEFAULT_LIMIT,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?string $search = null,
    ) {}
}
