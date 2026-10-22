<?php

namespace App\Actions\PurchaseRequests;

use App\Actions\Support\DocumentWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Domain\Canonical\ApprovalDecision;
use App\Integrations\Contracts\PurchaseRequestSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Authorize or reject a purchase request in the ERP, on behalf of a person.
 *
 * The write and audit rules are DocumentWrites'. The ESB takes no reason for
 * a purchase request rejection, so none is sent or audited.
 */
class DecidePurchaseRequest
{
    public function __construct(
        private readonly PurchaseRequestSource $purchaseRequests,
        private readonly DocumentWrites $writes,
    ) {}

    /**
     * @throws WriteAlreadyInFlight when another write on this document is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(string $number, ApprovalDecision $decision): void
    {
        $this->writes->decide(
            PurchaseRequestDocument::approvable(), $this->purchaseRequests->provider(), $number, $decision, null,
            fn () => $this->purchaseRequests->decidePurchaseRequest($number, $decision),
        );
    }
}
