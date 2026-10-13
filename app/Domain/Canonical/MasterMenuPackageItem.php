<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A menu that can be chosen within a package group.
 */
readonly class MasterMenuPackageItem implements JsonSerializable
{
    public function __construct(
        public int $menuId,
        public string $name,
        public ?string $code = null,
        public ?bool $active = null,
        public ?float $additionalPrice = null,
        public ?bool $defaultItem = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
