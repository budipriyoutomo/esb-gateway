<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A point-of-sale menu, as the ESB Core menu browse describes it ([ESB-012]).
 *
 * Only what /menu/browse returns: identity, names, category, and notes.
 * Prices, packages, and extras are not part of that operation.
 */
readonly class Menu implements JsonSerializable
{
    public function __construct(
        public string $provider,
        public ?int $id,
        public string $code,
        public string $name,
        public ?string $shortName = null,
        public ?int $categoryId = null,
        public ?string $categoryName = null,
        public ?int $categoryDetailId = null,
        public ?string $categoryDetailName = null,
        public ?string $notes = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
