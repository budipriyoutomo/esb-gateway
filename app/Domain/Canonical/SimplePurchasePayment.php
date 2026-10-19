<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A purchase payment the ERP lists against a simple purchase.
 *
 * `accountName` is the vendor's `bankAccountName`: the account it was paid
 * from.
 */
readonly class SimplePurchasePayment implements JsonSerializable
{
    public function __construct(
        public string $number,
        public ?string $date = null,
        public ?float $amount = null,
        public ?string $accountName = null,
        public ?string $statusName = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
