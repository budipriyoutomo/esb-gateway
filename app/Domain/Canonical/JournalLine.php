<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One debit / credit line of a journal: a general journal's detail
 * ([ESB-041]) or a memorial journal's ([ESB-043]). Amounts are the ERP's;
 * the gateway does not balance them.
 */
readonly class JournalLine implements JsonSerializable
{
    public function __construct(
        public ?int $id,
        public ?int $branchId,
        public ?string $branchName,
        public ?string $coaNo,
        public ?string $coaDescription,
        public ?string $costCenter,
        public ?string $project,
        public ?string $currencyName,
        public ?int $currencyId,
        public ?float $rate,
        public ?float $debit,
        public ?float $credit,
        public ?string $notes,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
