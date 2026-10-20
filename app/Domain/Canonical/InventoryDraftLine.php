<?php

namespace App\Domain\Canonical;

/**
 * One product line of an InventoryDraft. `requestQty` is a transfer
 * request's requested quantity; `purposeId` and `cost` (HPP per unit) are an
 * item journal's. Null is "not given".
 */
readonly class InventoryDraftLine
{
    public function __construct(
        public int $productDetailId,
        public int|float $qty,
        public int|float|null $requestQty = null,
        public ?int $purposeId = null,
        public int|float|null $cost = null,
    ) {}
}
