<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A supplier's bank account ([ESB-024]). Only the last four digits of the
 * account number are kept; the full number stays in the ERP.
 */
readonly class SupplierBankAccount implements JsonSerializable
{
    public function __construct(
        public ?int $bankId,
        public ?string $bankName,
        public ?string $accountName,
        /** e.g. "•••• 6789"; null when the ERP sent none. */
        public ?string $maskedAccountNumber,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
