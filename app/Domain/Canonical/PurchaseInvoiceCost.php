<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * An additional cost booked on a purchase invoice against a ledger account,
 * such as freight.
 */
readonly class PurchaseInvoiceCost implements JsonSerializable
{
    public function __construct(
        public string $accountNumber,
        public ?string $accountName = null,
        public ?float $amount = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
