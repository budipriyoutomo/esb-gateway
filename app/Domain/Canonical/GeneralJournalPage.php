<?php

namespace App\Domain\Canonical;

/**
 * One page of the general journal.
 */
final readonly class GeneralJournalPage
{
    /**
     * @param  list<GeneralJournalEntry>  $entries
     */
    public function __construct(
        public array $entries,
        public ?Pagination $pagination,
    ) {}
}
