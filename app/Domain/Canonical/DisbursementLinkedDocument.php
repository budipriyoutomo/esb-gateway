<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A purchase payment or supplier settlement against a disbursement
 * ([ESB-037]). Listed, not opened: those modules are not built.
 */
readonly class DisbursementLinkedDocument implements JsonSerializable
{
    public function __construct(
        public string $type,
        public string $number,
        public ?string $date,
        public ?float $total,
        public ?string $currencyName,
        public ?string $statusName,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
