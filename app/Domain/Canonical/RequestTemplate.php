<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A request template: the ERP's predefined list of products a branch
 * orders, which is where a new purchase request's lines come from.
 *
 * Transcribed from the ESB's GET /document-template/list result.
 */
readonly class RequestTemplate implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
