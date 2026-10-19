<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A single purchase order with its lines and the documents raised from it.
 *
 * Transcribed from the ESB's GET /purchase/purchase-order/{purchaseNum}
 * result (ADR-019: one real example, expect revision). The caveats on
 * PurchaseOrderSummary apply to dates, status, and amounts.
 *
 * `canEdit` / `canDelete` are the ERP's own `access` flags for this document
 * and credential. Null means the ERP did not say; a screen must then offer
 * the action and let the ERP refuse it, rather than guess.
 *
 * Not carried:
 *
 * - `images`: absolute URLs on the ESB's storage. The browser does not talk
 *   to the ESB's hosts (ADR-006).
 * - `supplierDefaultPIC`, `phoneNumber`, `address`, `emailSupplier`: contact
 *   details of a person at the supplier that no screen needs.
 * - `approval`, `approvalFlow`, `mapSupplierLink`, `access.unclose`,
 *   `flagImportDoc`, the `link*` and `printed*`/`emailed*` fields: no screen
 *   needs them yet, and the approval arrays' semantics are undocumented.
 */
readonly class PurchaseOrder implements JsonSerializable
{
    /**
     * @param  int|null  $dueDays  The vendor's `dueDay`: payment terms in days.
     * @param  string|null  $contactName  The supplier contact on the order
     *                                    (the vendor's `picName`).
     * @param  list<PurchaseOrderLine>  $lines
     * @param  list<PurchaseOrderLinkedDocument>  $goodsReceipts
     * @param  list<PurchaseOrderLinkedDocument>  $purchaseInvoices
     */
    public function __construct(
        public string $provider,
        public string $number,
        public ?string $date = null,
        public ?string $requiredDate = null,
        public ?string $purchaseTypeName = null,
        public ?int $branchId = null,
        public ?string $branchName = null,
        public ?int $supplierId = null,
        public ?string $supplierName = null,
        public ?string $supplierBranchName = null,
        public ?string $supplierAddress = null,
        public ?string $supplierPhone = null,
        public ?string $contactName = null,
        public ?int $currencyId = null,
        public ?string $currencyName = null,
        public ?string $currencySign = null,
        public ?float $rate = null,
        public ?int $dueDays = null,
        public ?string $costCenterName = null,
        public ?string $projectName = null,
        public ?float $total = null,
        public ?int $statusId = null,
        public ?string $statusName = null,
        public ?string $notes = null,
        public ?string $footNote = null,
        public ?string $purchaseRequestNumbers = null,
        public ?string $createdByName = null,
        public ?string $createdAt = null,
        public ?string $editedByName = null,
        public ?string $editedAt = null,
        public ?string $authorizedByName = null,
        public ?string $authorizedAt = null,
        public ?bool $canEdit = null,
        public ?bool $canDelete = null,
        public array $lines = [],
        public array $goodsReceipts = [],
        public array $purchaseInvoices = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
