<?php

namespace App\Domain\Canonical;

use JsonSerializable;

/**
 * A single simple purchase with its lines, costs, and payments.
 *
 * Transcribed from the ESB's GET /purchase/simple-purchase/{cashPurchaseNum}
 * result (ADR-019: one real example, expect revision). The caveats on
 * SimplePurchaseSummary apply to dates, status, and amounts.
 *
 * Not carried:
 *
 * - `image`: absolute URLs on the ESB's storage (ADR-006).
 * - `supplierDefaultPIC`, `phoneNumber`: a supplier person's contact details
 *   that no screen needs.
 * - `dueDate`: an integer beside the `cashPurchaseDueDate` string; whether it
 *   is days or something else is undocumented. TBD.
 * - `coaNo` and `description` on the header: undocumented meaning.
 * - `simplePurchaseExpirations`, `approval`, `approvalFlow`,
 *   `releasePaymentNum`, `exportCsvCounter`, `branchFlagActive`: no screen
 *   needs them yet.
 */
readonly class SimplePurchase implements JsonSerializable
{
    /**
     * @param  string|null  $paymentMethodName  The vendor's `paymentName`
     *                                          (e.g. cash, transfer).
     * @param  string|null  $contactName  The supplier contact (the vendor's
     *                                    `picName`).
     * @param  list<SimplePurchaseLine>  $lines
     * @param  list<SimplePurchaseCost>  $costs
     * @param  list<SimplePurchasePayment>  $payments
     */
    public function __construct(
        public string $provider,
        public string $number,
        public ?string $supplierInvoiceNumber = null,
        public ?string $date = null,
        public ?string $dueDate = null,
        public ?string $purchaseTypeName = null,
        public ?int $branchId = null,
        public ?string $branchName = null,
        public ?string $locationName = null,
        public ?int $supplierId = null,
        public ?string $supplierName = null,
        public ?string $supplierPhone = null,
        public ?string $contactName = null,
        public ?string $paymentMethodName = null,
        public ?int $currencyId = null,
        public ?string $currencyName = null,
        public ?string $currencySign = null,
        public ?float $rate = null,
        public ?string $costCenterName = null,
        public ?string $projectName = null,
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
        public array $payments = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
