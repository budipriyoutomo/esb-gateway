<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One purchase order as it appears in a list, independent of who supplied it.
 *
 * Transcribed from the ESB's GET /purchase/purchase-order row, the only
 * example that exists (ADR-019). The same caveats as PurchaseInvoiceSummary
 * apply: dates are the provider's strings (format TBD), status is the
 * provider's own id and label, and amounts are display-only.
 */
readonly class PurchaseOrderSummary implements JsonSerializable
{
    /**
     * @param  string  $number  The ERP's document number, e.g. PO202603200001,
     *                          and what the detail read is addressed by.
     * @param  string|null  $requiredDate  When the goods are needed.
     * @param  string|null  $purchaseRequestNumbers  The vendor's
     *                                               `purchaseRequestNums`, as
     *                                               sent. Its separator is not
     *                                               documented, so it is not split.
     */
    public function __construct(
        public string $provider,
        public string $number,
        public ?string $date = null,
        public ?string $requiredDate = null,
        public ?int $branchId = null,
        public ?string $branchName = null,
        public ?int $supplierId = null,
        public ?string $supplierName = null,
        public ?int $currencyId = null,
        public ?string $currencySign = null,
        public ?float $total = null,
        public ?int $statusId = null,
        public ?string $statusName = null,
        public ?string $purchaseRequestNumbers = null,
        public ?string $createdByName = null,
        public ?string $notes = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
