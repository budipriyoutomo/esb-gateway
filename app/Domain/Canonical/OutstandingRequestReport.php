<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One page of the PR outstanding report (ADR-034). `stageCounts` and
 * `overdueCount` cover every outstanding item of the period, whatever stage,
 * overdue filter or page is shown.
 */
readonly class OutstandingRequestReport implements JsonSerializable
{
    /**
     * @param  list<OutstandingRequestItem>  $items
     * @param  array<string, int>  $stageCounts  OutstandingStage value => items
     */
    public function __construct(
        public array $items,
        public Pagination $pagination,
        public array $stageCounts,
        public int $overdueCount,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
