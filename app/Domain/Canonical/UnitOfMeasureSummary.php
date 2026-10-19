<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A unit of measure as a list row (ESB Core GET /units, [ESB-020]).
 */
readonly class UnitOfMeasureSummary implements JsonSerializable
{
    public function __construct(
        public string $provider,
        public ?int $id,
        public string $name,
        public ?int $metricId = null,
        public ?string $metricName = null,
        public ?string $notes = null,
        public ?string $taxCode = null,
        public ?string $taxName = null,
        public ?bool $active = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
