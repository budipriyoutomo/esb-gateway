<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A mapping row of an account (the ESB's mapCoa, [ESB-025]). Type and
 * currency ids have no documented legend.
 */
readonly class CoaMapping implements JsonSerializable
{
    public function __construct(
        public ?int $id,
        public ?string $key,
        public ?int $typeId,
        public ?int $currencyId,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
