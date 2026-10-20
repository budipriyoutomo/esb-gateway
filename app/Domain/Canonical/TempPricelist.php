<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One temporary pricelist with its proposed prices (ESB Core
 * GET /pricelist/temp/{pricelistNum}, [ESB-027]).
 */
readonly class TempPricelist implements JsonSerializable
{
    /**
     * @param  list<TempPricelistLine>  $lines
     */
    public function __construct(
        public string $provider,
        public string $number,
        public ?int $supplierId,
        public ?string $supplierName,
        public ?string $currencyName,
        public ?int $currencyId,
        public ?string $priceDate,
        public ?string $expireDate,
        public ?int $statusId,
        public ?string $statusName,
        public BranchScope $branches,
        public array $lines = [],
        public ?string $createdBy = null,
        public ?string $createdAt = null,
        public ?string $editedBy = null,
        public ?string $editedAt = null,
        public ?string $rejectedBy = null,
        public ?string $rejectedAt = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
