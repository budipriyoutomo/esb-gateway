<?php

namespace App\Actions\PurchaseOrders;

use App\Actions\Support\DocumentWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Domain\Canonical\ApprovalDecision;
use App\Integrations\Contracts\PurchaseOrderSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Authorize or reject a purchase order in the ERP, on behalf of a person.
 *
 * The write and audit rules are DocumentWrites'.
 */
class DecidePurchaseOrder
{
    public function __construct(
        private readonly PurchaseOrderSource $purchaseOrders,
        private readonly DocumentWrites $writes,
    ) {}

    /**
     * @param  string|null  $reason  Required for a rejection; dropped for an
     *                               authorization.
     *
     * @throws WriteAlreadyInFlight when another write on this document is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(string $number, ApprovalDecision $decision, ?string $reason = null): void
    {
        $this->writes->decide(
            PurchaseOrderDocument::approvable(), $this->purchaseOrders->provider(), $number, $decision, $reason,
            fn (?string $reason) => $this->purchaseOrders->decidePurchaseOrder($number, $decision, $reason),
        );
    }
}
