<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A branch (outlet, warehouse, office) a purchase request can be raised for.
 *
 * Transcribed from the ESB's GET /branch result. Only what a picker needs.
 */
readonly class Branch implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $name,
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
