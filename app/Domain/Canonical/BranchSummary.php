<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A branch as a list row. ESB Core GET /branch returns only the id, code and
 * name ([ESB-051]); the status and the rest are in the detail.
 */
readonly class BranchSummary implements JsonSerializable
{
    public function __construct(
        public string $provider,
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
