<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * An add-on that can be ordered with a menu, e.g. extra topping.
 */
readonly class MasterMenuExtra implements JsonSerializable
{
    public function __construct(
        public ?int $extraId,
        public string $name,
        public ?bool $active = null,
        public ?float $minQty = null,
        public ?float $maxQty = null,
        public ?float $price = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
