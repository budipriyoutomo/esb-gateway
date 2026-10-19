<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A supplier as a list row (ESB Core GET /supplier, [ESB-024]).
 */
readonly class SupplierSummary implements JsonSerializable
{
    public function __construct(
        public string $provider,
        public ?int $id,
        public string $name,
        public ?string $code = null,
        public ?int $categoryId = null,
        public ?string $categoryName = null,
        public ?string $address = null,
        public ?string $contactPerson = null,
        public ?string $cellPhone = null,
        /** Credit terms in days. */
        public ?int $creditDays = null,
        public ?bool $vatSubject = null,
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
