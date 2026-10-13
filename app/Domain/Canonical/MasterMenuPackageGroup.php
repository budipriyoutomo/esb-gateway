<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A choice group inside a package menu, e.g. "Drink: pick 1 to 2".
 */
readonly class MasterMenuPackageGroup implements JsonSerializable
{
    /**
     * @param  list<MasterMenuPackageItem>  $items
     */
    public function __construct(
        public ?int $groupId,
        public string $name,
        public ?bool $active = null,
        public ?int $order = null,
        public ?float $minQty = null,
        public ?float $maxQty = null,
        public ?string $notes = null,
        public array $items = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
