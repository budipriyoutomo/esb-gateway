<?php

namespace App\Actions\PurchaseRequests;

use App\Actions\Support\GuardedEsbWrite;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Integrations\Contracts\PurchaseRequestSource;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Models\AuditLog;

/**
 * Delete a purchase request in the ERP, on behalf of a person.
 */
class DeletePurchaseRequest
{
    public function __construct(
        private readonly PurchaseRequestSource $purchaseRequests,
        private readonly GuardedEsbWrite $guard,
    ) {}

    /**
     * @throws WriteAlreadyInFlight when another write on this document is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(string $number): void
    {
        $provider = $this->purchaseRequests->provider();

        $this->guard->run(
            PurchaseRequestLocks::document($number),
            fn () => $this->purchaseRequests->deletePurchaseRequest($number),
            fn (EsbException $exception) => UnconfirmedWrite::record($provider, 'delete', $exception, [
                'number' => $number,
            ]),
        );

        AuditLog::record(
            action: AuditAction::PurchaseRequestDeleted,
            metadata: [
                'provider' => $provider,
                'number' => $number,
            ],
        );
    }
}
