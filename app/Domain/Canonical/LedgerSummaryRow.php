<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One row of the general ledger summary: a branch's debit and credit on an
 * account for a day ([ESB-042]). The ERP's figures.
 */
readonly class LedgerSummaryRow implements JsonSerializable
{
    public function __construct(
        public ?string $date,
        public ?int $branchId,
        public ?string $branchCode,
        public ?string $branchName,
        public string $accountNo,
        public ?string $accountDescription,
        public ?float $debit,
        public ?float $credit,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
