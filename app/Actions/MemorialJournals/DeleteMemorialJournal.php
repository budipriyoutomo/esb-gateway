<?php

namespace App\Actions\MemorialJournals;

use App\Actions\Support\DocumentWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Integrations\Contracts\MemorialJournalSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Delete a memorial journal in the ERP -- with its lines -- on behalf of a
 * person ([ESB-044]). The ERP refuses one whose journal was reconciled.
 */
class DeleteMemorialJournal
{
    public function __construct(
        private readonly MemorialJournalSource $journals,
        private readonly DocumentWrites $writes,
    ) {}

    /**
     * @throws WriteAlreadyInFlight when another write on this journal is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(string $number): void
    {
        $this->writes->delete(
            MemorialJournals::approvable(), $this->journals->provider(), $number,
            fn () => $this->journals->deleteJournal($number),
        );
    }
}
