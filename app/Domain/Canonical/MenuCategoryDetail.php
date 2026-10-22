<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One category detail of a MenuCategory ([ESB-047]).
 */
readonly class MenuCategoryDetail implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $code,
        public ?string $description,
        public ?float $maxOrderQty,
        public ?string $statusName,
        public ?string $buttonColor,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
