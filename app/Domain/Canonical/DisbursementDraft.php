<?php

namespace App\Domain\Canonical;

/**
 * A disbursement as the gateway's form fills it, to create or replace one
 * ([ESB-039]). Cash takes the cash / bank account; credit takes the
 * supplier, and optionally a credit term and reference. What the form does
 * not show is carried over on an update (DisbursementCarryOver).
 */
readonly class DisbursementDraft
{
    /**
     * @param  list<DisbursementDraftLine>  $lines
     */
    public function __construct(
        public string $date,
        public int $branchId,
        public DisbursementPaymentMethod $payment,
        public int $currencyId,
        public int|float $rate,
        public ?string $cashBankCoaNo,
        public ?int $supplierId,
        public ?int $creditTerm,
        public ?string $reference,
        public ?string $invoiceDueDate,
        public ?string $notes,
        public array $lines,
    ) {}
}
