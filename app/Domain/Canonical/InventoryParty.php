<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One side of an inventory document: where goods come from or go to. A
 * branch and / or a location, or a named counterpart (customer, supplier,
 * the source of a receipt) with its address and phone.
 */
readonly class InventoryParty implements JsonSerializable
{
    public function __construct(
        public ?int $branchId = null,
        public ?string $branchName = null,
        public ?int $locationId = null,
        public ?string $locationName = null,
        public ?string $name = null,
        public ?string $address = null,
        public ?string $phone = null,
    ) {}

    public function isEmpty(): bool
    {
        return array_filter(get_object_vars($this), static fn (mixed $value): bool => $value !== null) === [];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
