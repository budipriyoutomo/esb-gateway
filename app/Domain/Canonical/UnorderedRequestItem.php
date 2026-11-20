<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A purchase request item not yet (fully) made into an order ([ESB-053],
 * ADR-034), from the ESB's request monitoring.
 *
 * `unorderedQty` is the vendor's `sumQty`, documented as "Total requested
 * quantity" but observed on staging as the quantity not yet ordered. TBD with
 * the ESB team (docs/esb/open-questions.md §6.1).
 */
readonly class UnorderedRequestItem implements JsonSerializable
{
    /**
     * The vendor's request process for Transfer: such an item is sent from
     * another branch, never ordered. 1 ("- ALL -") and 2 (Purchase) can be.
     * Observed on staging, not documented (TBD).
     */
    public const TRANSFER_PROCESS = 3;

    public function __construct(
        public string $purchaseRequestNumber,
        public ?string $purchaseRequestDate,
        public ?string $requiredDate,
        public ?int $branchId,
        public ?string $branchName,
        public int $productDetailId,
        public ?string $productCode,
        public ?string $productName,
        public ?string $uomName,
        public float $unorderedQty,
        public ?int $requestProcessId = null,
        public ?string $requestProcessName = null,
        public ?string $supplierName = null,
    ) {}

    public function canBeOrdered(): bool
    {
        return $this->requestProcessId !== self::TRANSFER_PROCESS;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
