<?php

namespace App\Domain\Canonical;

/**
 * The filters a caller may ask a BranchSource for. Values are already
 * validated; null means "omit this filter".
 *
 * GET /branch is not paginated and has no search parameter ([ESB-051]), so
 * search and paging are applied by the gateway; only the status reaches the
 * ESB.
 */
readonly class BranchQuery
{
    /** The gateway's own ceiling, matching the other master data lists. */
    public const MAX_LIMIT = 100;

    public const DEFAULT_LIMIT = 20;

    public function __construct(
        public int $page = 1,
        public int $limit = self::DEFAULT_LIMIT,
        public ?string $search = null,
        public ?bool $activeOnly = null,
    ) {}
}
