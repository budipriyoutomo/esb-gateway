<?php

namespace App\Actions\PurchaseInvoices;

use App\Actions\Support\DocumentWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Integrations\Contracts\PurchaseInvoiceSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Delete a purchase invoice in the ERP, on behalf of a person.
 *
 * The write and audit rules are DocumentWrites'.
 */
class DeletePurchaseInvoice
{
    public function __construct(
        private readonly PurchaseInvoiceSource $purchaseInvoices,
        private readonly DocumentWrites $writes,
    ) {}

    /**
     * @throws WriteAlreadyInFlight when another write on this document is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(string $number): void
    {
        $this->writes->delete(
            PurchaseInvoiceDocument::approvable(), $this->purchaseInvoices->provider(), $number,
            fn () => $this->purchaseInvoices->deletePurchaseInvoice($number),
        );
    }
}
