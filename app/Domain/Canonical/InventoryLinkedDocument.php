<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A document an inventory document points to or comes with, e.g. the
 * purchase requests behind a transfer request or the invoices of a receipt.
 */
readonly class InventoryLinkedDocument implements JsonSerializable
{
    public function __construct(
        public string $type,
        public string $number,
        public ?string $date = null,
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
