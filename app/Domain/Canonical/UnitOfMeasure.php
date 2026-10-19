<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One unit of measure (ESB Core GET /units/{uomID}, [ESB-020]). The detail
 * carries the reference tax id, not its code or name.
 */
readonly class UnitOfMeasure implements JsonSerializable
{
    public function __construct(
        public string $provider,
        public int $id,
        public string $name,
        public ?int $metricId = null,
        public ?string $metricName = null,
        public ?string $notes = null,
        public ?int $taxId = null,
        public ?bool $active = null,
        public ?string $createdBy = null,
        public ?string $createdAt = null,
        public ?string $editedBy = null,
        public ?string $editedAt = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
