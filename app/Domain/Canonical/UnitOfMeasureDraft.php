<?php

namespace App\Domain\Canonical;

/**
 * A unit to create, or to replace an existing one with ([ESB-030]).
 */
final readonly class UnitOfMeasureDraft
{
    public function __construct(
        public string $name,
        public int $metricId,
        public ?int $taxId = null,
        public ?string $notes = null,
    ) {}
}
