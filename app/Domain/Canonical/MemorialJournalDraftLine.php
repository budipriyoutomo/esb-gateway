<?php

namespace App\Domain\Canonical;

/**
 * One debit or credit line of a MemorialJournalDraft: exactly one of $debit
 * and $credit is set. $id is the ERP's id of the line it replaces, if any --
 * used only to carry that line's cost center and project over.
 */
readonly class MemorialJournalDraftLine
{
    public function __construct(
        public ?int $id,
        public int $branchId,
        public string $coaNo,
        public int $currencyId,
        public int|float $rate,
        public int|float|null $debit,
        public int|float|null $credit,
        public ?string $notes,
    ) {}
}
