<?php

namespace App\Actions\Disbursements;

use App\Actions\Support\DocumentWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Domain\Canonical\ApprovalDecision;
use App\Integrations\Contracts\DisbursementSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Authorize or reject a disbursement in the ERP, on behalf of a person
 * ([ESB-038]). The write and audit rules are DocumentWrites'.
 */
class DecideDisbursement
{
    public function __construct(
        private readonly DisbursementSource $disbursements,
        private readonly DocumentWrites $writes,
    ) {}

    /**
     * @throws WriteAlreadyInFlight when another write on this disbursement is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(string $number, ApprovalDecision $decision, ?string $reason = null): void
    {
        $this->writes->decide(
            Disbursements::approvable(), $this->disbursements->provider(), $number, $decision, $reason,
            fn (?string $reason) => $this->disbursements->decideDisbursement($number, $decision, $reason),
        );
    }
}
