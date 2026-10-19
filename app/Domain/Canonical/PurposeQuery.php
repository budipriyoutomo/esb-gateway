<?php

namespace App\Domain\Canonical;

/**
 * The filters a caller may ask a PurposeSource for. Values are already
 * validated; null means "omit this filter".
 *
 * The ESB's documented GET /purpose filters ([ESB-015]), except `sort`: the
 * vendor names only purposeName and flagActive as examples, not a full list,
 * so the ESB default (purposeName) applies.
 */
readonly class PurposeQuery
{
    /**
     * The gateway's own ceiling, matching the other Core list reads: the ESB
     * rejects a limit above 100 elsewhere with EC03100040.
     */
    public const MAX_LIMIT = 100;

    public const DEFAULT_LIMIT = 20;

    public function __construct(
        public int $page = 1,
        public int $limit = self::DEFAULT_LIMIT,
        public ?string $name = null,
        public ?string $accountNo = null,
        public ?bool $activeOnly = null,
    ) {}
}
