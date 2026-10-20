<?php

namespace App\Domain\Canonical;

/**
 * Filters for an inventory document list ([ESB-033]). Null is "no filter".
 * A filter the kind's list does not offer is ignored by the adapter.
 */
final readonly class InventoryDocumentQuery
{
    public const DEFAULT_LIMIT = 20;

    public const MAX_LIMIT = 100;

    public function __construct(
        public int $page = 1,
        public int $limit = self::DEFAULT_LIMIT,
        public ?string $number = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?int $statusId = null,
        public ?int $branchId = null,
        public ?string $search = null,
    ) {}
}
