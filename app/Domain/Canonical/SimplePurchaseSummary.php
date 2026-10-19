<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One simple purchase as it appears in a list, independent of who supplied it.
 *
 * Transcribed from the ESB's GET /purchase/simple-purchase row, the only
 * example that exists (ADR-019). The same caveats as PurchaseInvoiceSummary
 * apply: dates are the provider's strings (format TBD), status is the
 * provider's own id and label, and amounts are display-only.
 */
readonly class SimplePurchaseSummary implements JsonSerializable
{
    /**
     * @param  string  $number  The ERP's document number (the vendor's
     *                          `cashPurchaseNum`), and what the detail read is
     *                          addressed by.
     * @param  string|null  $supplierInvoiceNumber  The supplier's own receipt
     *                                              or invoice number.
     */
    public function __construct(
        public string $provider,
        public string $number,
        public ?string $supplierInvoiceNumber = null,
        public ?string $date = null,
        public ?int $branchId = null,
        public ?string $branchName = null,
        public ?int $supplierId = null,
        public ?string $supplierName = null,
        public ?string $currencySign = null,
        public ?float $total = null,
        public ?int $statusId = null,
        public ?string $statusName = null,
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
