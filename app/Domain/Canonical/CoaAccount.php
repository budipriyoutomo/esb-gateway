<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One account (ESB Core GET /accounting/coa/{coaNo}, [ESB-025]).
 */
readonly class CoaAccount implements JsonSerializable
{
    /**
     * @param  list<CoaMapping>  $mappings
     */
    public function __construct(
        public string $provider,
        public string $coaNo,
        public string $description,
        public ?int $level = null,
        public ?int $order = null,
        public ?int $branchId = null,
        public ?string $currency = null,
        public ?bool $moduleAccount = null,
        public ?bool $allowBudget = null,
        public ?bool $active = null,
        public ?string $additionalInfo = null,
        public ?string $releasePaymentCode = null,
        public array $mappings = [],
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
