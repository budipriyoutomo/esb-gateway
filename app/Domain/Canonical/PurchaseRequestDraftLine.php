<?php

namespace App\Domain\Canonical;

/**
 * One line of a purchase request being created or replaced.
 */
readonly class PurchaseRequestDraftLine
{
    public function __construct(
        public int $productDetailId,
        public int $requestProcessId,
        public float $qty,
        public ?string $notes = null,
    ) {}
}
