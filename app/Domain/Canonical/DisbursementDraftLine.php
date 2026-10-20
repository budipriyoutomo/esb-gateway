<?php

namespace App\Domain\Canonical;

/**
 * One account line of a DisbursementDraft.
 */
readonly class DisbursementDraftLine
{
    public function __construct(
        public int $branchId,
        public string $coaNo,
        public string $description,
        public int|float $amount,
    ) {}
}
