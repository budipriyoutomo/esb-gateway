<?php

namespace App\Actions\PurchaseInvoices;

use App\Actions\Support\GuardedEsbWrite;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Integrations\Contracts\PurchaseInvoiceSource;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Models\AuditLog;

/**
 * Delete a purchase invoice in the ERP, on behalf of a person.
 */
class DeletePurchaseInvoice
{
    public function __construct(
        private readonly PurchaseInvoiceSource $purchaseInvoices,
        private readonly GuardedEsbWrite $guard,
    ) {}

    /**
     * @throws WriteAlreadyInFlight when another write on this document is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(string $number): void
    {
        $provider = $this->purchaseInvoices->provider();

        $this->guard->run(
            PurchaseInvoiceLocks::document($number),
            fn () => $this->purchaseInvoices->deletePurchaseInvoice($number),
            fn (EsbException $exception) => UnconfirmedWrite::record($provider, 'delete', $number, $exception),
        );

        AuditLog::record(
            action: AuditAction::PurchaseInvoiceDeleted,
            metadata: [
                'provider' => $provider,
                'number' => $number,
            ],
        );
    }
}
