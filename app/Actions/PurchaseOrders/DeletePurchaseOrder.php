<?php

namespace App\Actions\PurchaseOrders;

use App\Actions\Support\DocumentWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Integrations\Contracts\PurchaseOrderSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Delete a purchase order in the ERP, on behalf of a person.
 *
 * The write and audit rules are DocumentWrites'.
 */
class DeletePurchaseOrder
{
    public function __construct(
        private readonly PurchaseOrderSource $purchaseOrders,
        private readonly DocumentWrites $writes,
    ) {}

    /**
     * @throws WriteAlreadyInFlight when another write on this document is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(string $number): void
    {
        $this->writes->delete(
            PurchaseOrderDocument::approvable(), $this->purchaseOrders->provider(), $number,
            fn () => $this->purchaseOrders->deletePurchaseOrder($number),
        );
    }
}
