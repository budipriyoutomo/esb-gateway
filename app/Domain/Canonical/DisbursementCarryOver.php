<?php

namespace App\Domain\Canonical;

/**
 * What an edit of a disbursement must send back unchanged because the
 * gateway's form does not show it ([ESB-039]): the cost center, the project
 * and the bank details -- the account number whole, as the ERP has it. Never
 * serialized: it stays on the server.
 */
readonly class DisbursementCarryOver
{
    public function __construct(
        public ?int $costCenterId = null,
        public ?int $projectId = null,
        public ?string $bankName = null,
        public ?string $bankAccountName = null,
        public ?string $bankAccountNumber = null,
    ) {}
}
