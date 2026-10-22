<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\ApprovalDecision;
use App\Domain\Canonical\MemorialJournal;
use App\Domain\Canonical\MemorialJournalCarryOver;
use App\Domain\Canonical\MemorialJournalDraft;
use App\Domain\Canonical\MemorialJournalPage;
use App\Domain\Canonical\MemorialJournalQuery;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Reads memorial journals ([ESB-043]), changes their state ([ESB-044]),
 * creates and replaces them ([ESB-045]) and attaches images ([ESB-046]).
 */
interface MemorialJournalSource extends OutboundAdapter
{
    /**
     * @throws EsbException
     */
    public function listJournals(MemorialJournalQuery $query): MemorialJournalPage;

    /**
     * The journal with this number, or null when the provider answers
     * successfully with none.
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function findJournal(string $number): ?MemorialJournal;

    /**
     * @param  string|null  $reason  Sent with a rejection only.
     *
     * @throws EsbException
     */
    public function decideJournal(string $number, ApprovalDecision $decision, ?string $reason = null): void;

    /**
     * @throws EsbException
     */
    public function deleteJournal(string $number): void;

    /**
     * The new number, or null when the provider does not say it.
     *
     * @throws EsbException
     */
    public function createJournal(MemorialJournalDraft $draft): ?string;

    /**
     * Replace the journal with the draft, sending back what the form does
     * not show.
     *
     * @throws EsbException
     */
    public function updateJournal(string $number, MemorialJournalDraft $draft, MemorialJournalCarryOver $carryOver): void;

    /**
     * Add one image to the journal's asset images.
     *
     * @throws EsbException
     */
    public function attachImage(string $number, string $contents, string $filename): void;
}
