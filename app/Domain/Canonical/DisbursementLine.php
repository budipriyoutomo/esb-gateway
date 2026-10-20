<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One account line of a disbursement ([ESB-037]), or one row the ERP read
 * from its Excel disbursement form ([ESB-040]).
 */
readonly class DisbursementLine implements JsonSerializable
{
    public function __construct(
        public ?int $branchId,
        public ?string $branchName,
        public ?string $coaNo,
        public ?string $coaDescription,
        public ?string $description,
        public ?float $amount,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
