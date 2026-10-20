<?php

namespace App\Domain\Canonical;

/**
 * One page of the memorial journal list.
 */
final readonly class MemorialJournalPage
{
    /**
     * @param  list<MemorialJournalSummary>  $journals
     */
    public function __construct(
        public array $journals,
        public ?Pagination $pagination,
    ) {}
}
