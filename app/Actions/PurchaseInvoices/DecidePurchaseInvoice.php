<?php

namespace App\Actions\PurchaseInvoices;

use App\Actions\Support\DocumentWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Domain\Canonical\ApprovalDecision;
use App\Integrations\Contracts\PurchaseInvoiceSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Authorize or reject a purchase invoice in the ERP, on behalf of a person.
 *
 * The write and audit rules are DocumentWrites'.
 */
class DecidePurchaseInvoice
{
    public function __construct(
        private readonly PurchaseInvoiceSource $purchaseInvoices,
        private readonly DocumentWrites $writes,
    ) {}

    /**
     * @throws WriteAlreadyInFlight when another write on this document is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(string $number, ApprovalDecision $decision): void
    {
        $this->writes->decide(
            PurchaseInvoiceDocument::approvable(), $this->purchaseInvoices->provider(), $number, $decision, null,
            fn () => $this->purchaseInvoices->decidePurchaseInvoice($number, $decision),
        );
    }
}
