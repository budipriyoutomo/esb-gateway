<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A supplier's bank account ([ESB-024]). The account number is shown in
 * full (business decision 2026-10-22).
 */
readonly class SupplierBankAccount implements JsonSerializable
{
    public function __construct(
        public ?int $bankId,
        public ?string $bankName,
        public ?string $accountName,
        /** As the ERP sent it, trimmed; null when it sent none. */
        public ?string $accountNumber,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
