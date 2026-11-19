<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One branch, as ESB Core GET /branch/{branchID} returns it ([ESB-051]).
 *
 * Type, brand, timezone and district ids are carried as ids: the vendor
 * documents no endpoint naming them for this result (TBD). Image URLs are
 * not carried (ADR-006).
 */
readonly class BranchDetail implements JsonSerializable
{
    /**
     * @param  array{cashier: int|null, order: int|null, tab: int|null, serverOnly: int|null}  $licences
     */
    public function __construct(
        public string $provider,
        public int $id,
        public string $name,
        public ?string $code = null,
        public ?string $externalCode = null,
        public ?string $esbOrderName = null,
        public ?int $branchTypeId = null,
        public ?int $brandId = null,
        public ?bool $active = null,
        public ?string $address = null,
        public ?string $phone = null,
        public ?string $postalCode = null,
        public ?string $latitude = null,
        public ?string $longitude = null,
        public ?string $openDate = null,
        public ?string $closeDate = null,
        public ?string $taxName = null,
        public int|float|null $taxValue = null,
        public ?string $additionalTaxName = null,
        public int|float|null $additionalTaxValue = null,
        public ?string $vatName = null,
        public ?int $timezoneId = null,
        public ?string $posVersion = null,
        public array $licences = ['cashier' => null, 'order' => null, 'tab' => null, 'serverOnly' => null],
        public ?string $printingHeader = null,
        public ?string $printingFooter = null,
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
