<?php

namespace App\Domain\Canonical;

/**
 * The promotion list's filters ([ESB-049]). The ESB returns the whole
 * filtered list; the gateway pages it.
 */
readonly class PromotionQuery
{
    public const LIMIT = 20;

    public function __construct(
        public int $page = 1,
        public ?string $branchCode = null,
        public ?string $promotionCode = null,
    ) {}
}
