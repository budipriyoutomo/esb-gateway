<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * Another menu referred to by id, e.g. a related menu.
 */
readonly class MasterMenuReference implements JsonSerializable
{
    public function __construct(
        public int $menuId,
        public ?string $name = null,
        public ?string $code = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
