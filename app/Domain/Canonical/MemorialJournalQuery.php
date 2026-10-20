<?php

namespace App\Domain\Canonical;

/**
 * Filters for the memorial journal list ([ESB-043]). Null is "no filter".
 */
final readonly class MemorialJournalQuery
{
    public const DEFAULT_LIMIT = 20;

    public const MAX_LIMIT = 100;

    /**
     * The statuses the vendor documents for memorial journals, in workflow
     * order.
     */
    public const STATUSES = [
        1 => 'New',
        38 => 'Waiting For Approval',
        3 => 'Authorized',
        2 => 'Rejected',
    ];

    public function __construct(
        public int $page = 1,
        public int $limit = self::DEFAULT_LIMIT,
        public ?string $number = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?int $statusId = null,
    ) {}
}
