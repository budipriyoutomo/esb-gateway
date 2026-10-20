<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\MemorialJournal;
use App\Domain\Canonical\MemorialJournalPage;
use App\Domain\Canonical\MemorialJournalQuery;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Reads memorial journals ([ESB-043]).
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
}
