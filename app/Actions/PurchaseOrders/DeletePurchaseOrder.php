<?php

namespace App\Actions\PurchaseOrders;

use App\Actions\Support\GuardedEsbWrite;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Integrations\Contracts\PurchaseOrderSource;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Models\AuditLog;

/**
 * Delete a purchase order in the ERP, on behalf of a person.
 */
class DeletePurchaseOrder
{
    public function __construct(
        private readonly PurchaseOrderSource $purchaseOrders,
        private readonly GuardedEsbWrite $guard,
    ) {}

    /**
     * @throws WriteAlreadyInFlight when another write on this document is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(string $number): void
    {
        $provider = $this->purchaseOrders->provider();

        $this->guard->run(
            PurchaseOrderLocks::document($number),
            fn () => $this->purchaseOrders->deletePurchaseOrder($number),
            fn (EsbException $exception) => UnconfirmedWrite::record($provider, 'delete', $number, $exception),
        );

        AuditLog::record(
            action: AuditAction::PurchaseOrderDeleted,
            metadata: [
                'provider' => $provider,
                'number' => $number,
            ],
        );
    }
}
