<?php

namespace App\Actions\Disbursements;

use App\Actions\Support\DocumentWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Integrations\Contracts\DisbursementSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Delete a disbursement in the ERP -- with its journal, payable,
 * attachments and approvals -- on behalf of a person ([ESB-038]).
 */
class DeleteDisbursement
{
    public function __construct(
        private readonly DisbursementSource $disbursements,
        private readonly DocumentWrites $writes,
    ) {}

    /**
     * @throws WriteAlreadyInFlight when another write on this disbursement is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(string $number): void
    {
        $this->writes->delete(
            Disbursements::approvable(), $this->disbursements->provider(), $number,
            fn () => $this->disbursements->deleteDisbursement($number),
        );
    }
}
