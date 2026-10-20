<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One general ledger entry of an account ([ESB-042]). Debit and credit are
 * the ERP's; no balance is derived from them.
 */
readonly class LedgerEntry implements JsonSerializable
{
    public function __construct(
        public ?string $date,
        public ?int $branchId,
        public ?string $branchName,
        public string $coaNo,
        public ?string $coaDescription,
        public ?string $transactionType,
        public ?string $reference,
        public ?string $notes,
        public ?string $info,
        public ?float $debit,
        public ?float $credit,
        public ?string $costCenter,
        public ?string $project,
        public ?string $createdAt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
