<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A non-material cost booked on a bill of material, against a chart-of-accounts
 * number ([ESB-014]).
 */
readonly class BillOfMaterialCost implements JsonSerializable
{
    public function __construct(
        public ?string $accountNo,
        public ?string $description,
        public ?float $total,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
