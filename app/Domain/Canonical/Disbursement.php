<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One disbursement with its lines ([ESB-037]). The bank account number is
 * shown in full (business decision 2026-10-22); the carry-over an update
 * sends back is not serialized.
 */
readonly class Disbursement implements JsonSerializable
{
    /**
     * @param  list<DisbursementLine>  $lines
     * @param  list<DisbursementLinkedDocument>  $linkedDocuments
     */
    public function __construct(
        public string $provider,
        public string $number,
        public ?string $date,
        public ?int $statusId,
        public ?string $statusName,
        public ?int $branchId,
        public ?string $branchName,
        public ?int $paymentId,
        public ?string $paymentName,
        public ?string $cashBankCoaNo,
        public ?int $supplierId,
        public ?string $supplierName,
        public ?int $currencyId,
        public ?string $currencyName,
        public ?string $currencySign,
        public ?float $rate,
        public ?int $creditTerm,
        public ?string $invoiceDueDate,
        public ?string $reference,
        public ?string $notes,
        public ?string $bankName,
        public ?string $bankAccountName,
        public ?string $bankAccountNumber,
        public ?string $costCenter,
        public ?string $project,
        public ?float $grandTotal,
        public ?string $releasePaymentCode,
        public array $lines = [],
        public array $linkedDocuments = [],
        public int $attachmentCount = 0,
        public ?string $createdBy = null,
        public ?string $createdAt = null,
        public ?string $editedBy = null,
        public ?string $editedAt = null,
        public ?string $authorizedBy = null,
        public ?string $authorizedAt = null,
        public DisbursementCarryOver $carryOver = new DisbursementCarryOver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $values = get_object_vars($this);
        unset($values['carryOver']);

        return $values;
    }
}
