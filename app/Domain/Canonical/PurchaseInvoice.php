<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A single purchase invoice with its lines and costs.
 *
 * Transcribed from the ESB's GET /purchase/purchase-invoices/{purchaseInvoiceNum}
 * result (ADR-019: one real example, expect revision). The caveats on
 * PurchaseInvoiceSummary apply to dates, status, and amounts.
 *
 * Not carried:
 *
 * - `printData` and `imageAttachments`: the ESB's print layout and absolute
 *   URLs on its storage. The browser does not talk to the ESB's hosts
 *   (ADR-006); attachments wait for a gateway-side proxy.
 * - `unverifiedDetails`, `supplierDueDate`, `isJournalExist`,
 *   `isStockCardExist`, and the `link*` fields: undocumented meaning, and no
 *   screen needs them yet.
 */
readonly class PurchaseInvoice implements JsonSerializable
{
    /**
     * @param  string|null  $taxInvoiceNumber  The vendor's `vatInvoiceNum`
     *                                         (the tax invoice, faktur pajak).
     * @param  int|null  $dueDays  The vendor's `dueDay`: payment terms in days.
     * @param  list<PurchaseInvoiceLine>  $lines
     * @param  list<PurchaseInvoiceCost>  $costs
     */
    public function __construct(
        public string $provider,
        public string $number,
        public ?string $supplierInvoiceNumber = null,
        public ?string $taxInvoiceNumber = null,
        public ?string $date = null,
        public ?string $dueDate = null,
        public ?int $dueDays = null,
        public ?string $referenceDate = null,
        public ?int $branchId = null,
        public ?string $branchName = null,
        public ?int $supplierId = null,
        public ?string $supplierName = null,
        public ?int $currencyId = null,
        public ?string $currencyName = null,
        public ?string $currencySign = null,
        public ?float $rate = null,
        public ?float $referenceTotal = null,
        public ?float $costTotal = null,
        public ?float $total = null,
        public ?int $statusId = null,
        public ?string $statusName = null,
        public ?string $notes = null,
        public ?string $createdByName = null,
        public ?string $createdAt = null,
        public ?string $editedByName = null,
        public ?string $editedAt = null,
        public ?string $authorizedByName = null,
        public ?string $authorizedAt = null,
        public array $lines = [],
        public array $costs = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
