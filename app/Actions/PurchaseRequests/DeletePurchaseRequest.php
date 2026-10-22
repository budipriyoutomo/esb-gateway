<?php

namespace App\Actions\PurchaseRequests;

use App\Actions\Support\DocumentWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Integrations\Contracts\PurchaseRequestSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Delete a purchase request in the ERP, on behalf of a person.
 *
 * The write and audit rules are DocumentWrites'.
 */
class DeletePurchaseRequest
{
    public function __construct(
        private readonly PurchaseRequestSource $purchaseRequests,
        private readonly DocumentWrites $writes,
    ) {}

    /**
     * @throws WriteAlreadyInFlight when another write on this document is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(string $number): void
    {
        $this->writes->delete(
            PurchaseRequestDocument::approvable(), $this->purchaseRequests->provider(), $number,
            fn () => $this->purchaseRequests->deletePurchaseRequest($number),
        );
    }
}
