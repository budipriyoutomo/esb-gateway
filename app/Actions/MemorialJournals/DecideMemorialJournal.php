<?php

namespace App\Actions\MemorialJournals;

use App\Actions\Support\DocumentWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Domain\Canonical\ApprovalDecision;
use App\Integrations\Contracts\MemorialJournalSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Authorize or reject a memorial journal in the ERP, on behalf of a person
 * ([ESB-044]). The write and audit rules are DocumentWrites'.
 */
class DecideMemorialJournal
{
    public function __construct(
        private readonly MemorialJournalSource $journals,
        private readonly DocumentWrites $writes,
    ) {}

    /**
     * @throws WriteAlreadyInFlight when another write on this journal is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(string $number, ApprovalDecision $decision, ?string $reason = null): void
    {
        $this->writes->decide(
            MemorialJournals::approvable(), $this->journals->provider(), $number, $decision, $reason,
            fn (?string $reason) => $this->journals->decideJournal($number, $decision, $reason),
        );
    }
}
