<?php

namespace App\Domain\Canonical;

/**
 * A memorial journal to create or replace ([ESB-045]), as validated by
 * SaveMemorialJournalRequest. Whether its lines balance is the ERP's to
 * judge.
 */
readonly class MemorialJournalDraft
{
    /**
     * @param  list<MemorialJournalDraftLine>  $lines
     */
    public function __construct(
        public string $date,
        public ?string $notes,
        public array $lines,
    ) {}
}
