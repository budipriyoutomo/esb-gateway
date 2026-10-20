<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A temporary (proposed) pricelist as a list row (ESB Core
 * GET /pricelist/temp, [ESB-027]).
 */
readonly class TempPricelistSummary implements JsonSerializable
{
    public function __construct(
        public string $provider,
        public string $number,
        public ?string $supplierName,
        public ?string $currencyName,
        public ?string $priceDate,
        public ?string $expireDate,
        public ?int $statusId,
        public ?string $statusName,
        public BranchScope $branches,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
