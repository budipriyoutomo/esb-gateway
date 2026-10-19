<?php

namespace App\Domain\Canonical;

/**
 * Filters for the chart of accounts. Applied in the gateway: the provider's
 * list is unpaginated and takes no filters ([ESB-025]).
 */
readonly class CoaAccountQuery
{
    public const LIMIT = 50;

    public const LEVELS = [1, 2, 3, 4];

    public function __construct(
        public int $page = 1,
        public ?string $search = null,
        public ?int $level = null,
        public int $limit = self::LIMIT,
    ) {}
}
