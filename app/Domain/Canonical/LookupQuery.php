<?php

namespace App\Domain\Canonical;

/**
 * What a picker asks for: optional search text, a page, and a branch for
 * lists that belong to one.
 */
readonly class LookupQuery
{
    public const LIMIT = 20;

    public const MAX_SEARCH = 100;

    public function __construct(
        public ?string $search = null,
        public int $page = 1,
        public ?int $branchId = null,
        public int $limit = self::LIMIT,
    ) {}
}
