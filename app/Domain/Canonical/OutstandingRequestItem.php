<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One row of the PR outstanding report (ADR-034).
 *
 * `purchaseRequestNumbers` is one number for a not ordered item, and the
 * order's `purchaseRequestNums` as sent (comma separated) for an ordered one:
 * the ERP does not say which of an order's requests a line came from.
 * `outstandingQty` is what is still missing: not yet ordered, not yet
 * received, or requested minus received (negative when more came).
 * `ageDays` counts from the request date (not ordered) or the order date;
 * `overdueDays` from the required date, 0 while it has not passed.
 */
readonly class OutstandingRequestItem implements JsonSerializable
{
    public function __construct(
        public OutstandingStage $stage,
        public ?string $purchaseRequestNumbers,
        public ?string $purchaseRequestDate,
        public ?string $purchaseOrderNumber,
        public ?string $purchaseOrderDate,
        public ?string $purchaseOrderStatus,
        public ?string $requiredDate,
        public ?string $branchName,
        public ?string $supplierName,
        public ?string $productCode,
        public ?string $productName,
        public ?string $uomName,
        public ?float $requestedQty,
        public ?float $orderedQty,
        public ?float $receivedQty,
        public float $outstandingQty,
        public ?int $ageDays,
        public int $overdueDays,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [...get_object_vars($this), 'stage' => $this->stage->value];
    }
}
