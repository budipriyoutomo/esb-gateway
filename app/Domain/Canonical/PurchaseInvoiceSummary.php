<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * One purchase invoice as it appears in a list, independent of who supplied it.
 *
 * Transcribed from the ESB's GET /purchase/purchase-invoices row, the only
 * example that exists (ADR-019). The same caveats as PurchaseRequestSummary
 * apply: dates are the provider's strings (format TBD) and status is the
 * provider's own id and label (meaning TBD).
 *
 * Amounts are floats because the vendor types them as JSON `number`. They are
 * for display only: the gateway does no arithmetic on them, and the ERP stays
 * the authority on every total.
 */
readonly class PurchaseInvoiceSummary implements JsonSerializable
{
    /**
     * @param  string  $number  The ERP's document number, e.g. PI202603260005,
     *                          and what the detail read is addressed by.
     * @param  string|null  $supplierInvoiceNumber  The supplier's own number
     *                                              for the invoice.
     */
    public function __construct(
        public string $provider,
        public string $number,
        public ?string $supplierInvoiceNumber = null,
        public ?string $date = null,
        public ?string $dueDate = null,
        public ?int $branchId = null,
        public ?string $branchName = null,
        public ?int $supplierId = null,
        public ?string $supplierName = null,
        public ?int $currencyId = null,
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
