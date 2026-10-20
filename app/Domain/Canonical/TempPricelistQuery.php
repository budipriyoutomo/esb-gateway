<?php

namespace App\Domain\Canonical;

/**
 * Filters for temporary pricelists. Null means "omit this filter".
 */
readonly class TempPricelistQuery
{
    /**
     * The statuses the vendor documents for this filter ([ESB-027]).
     */
    public const STATUSES = [
        38 => 'Waiting For Approval',
        2 => 'Rejected',
    ];

    public function __construct(
        public int $page = 1,
        public int $limit = 20,
        /** The ESB's indexSearch. */
        public ?string $search = null,
        public ?int $statusId = null,
    ) {}
}
