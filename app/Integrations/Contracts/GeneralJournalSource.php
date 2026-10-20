<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\GeneralJournalPage;
use App\Domain\Canonical\GeneralJournalQuery;
use App\Domain\Canonical\JournalLinePage;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Reads the general journal ([ESB-041]).
 */
interface GeneralJournalSource extends OutboundAdapter
{
    /**
     * @throws EsbException
     */
    public function listEntries(GeneralJournalQuery $query): GeneralJournalPage;

    /**
     * One page of a journal's lines.
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function journalLines(int $journalHeadId, int $page): JournalLinePage;
}
