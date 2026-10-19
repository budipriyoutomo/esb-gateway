<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * An additional cost booked on a simple purchase against a ledger account,
 * such as parking or delivery.
 */
readonly class SimplePurchaseCost implements JsonSerializable
{
    public function __construct(
        public string $accountNumber,
        public ?string $description = null,
        public ?float $amount = null,
        public ?string $notes = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
