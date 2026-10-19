<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A document the ERP lists against a purchase order: a goods receipt made
 * from it, or a purchase invoice that bills it.
 *
 * `total` is only sent for invoices.
 */
readonly class PurchaseOrderLinkedDocument implements JsonSerializable
{
    public function __construct(
        public string $number,
        public ?string $date = null,
        public ?string $statusName = null,
        public ?float $total = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
