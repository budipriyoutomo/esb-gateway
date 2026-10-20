<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One row of the disbursement list ([ESB-037]). The total and the overdue
 * flag are the ERP's.
 */
readonly class DisbursementSummary implements JsonSerializable
{
    public function __construct(
        public string $provider,
        public string $number,
        public ?string $date,
        public ?int $branchId,
        public ?string $branchName,
        public ?int $supplierId,
        public ?string $supplierName,
        public ?string $paymentMethod,
        public ?string $reference,
        public ?string $notes,
        public ?float $grandTotal,
        public ?string $invoiceDueDate,
        public ?bool $overdue,
        public ?bool $hasApproval,
        public ?int $statusId,
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
