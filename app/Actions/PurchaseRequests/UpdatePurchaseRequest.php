<?php

namespace App\Actions\PurchaseRequests;

use App\Actions\Support\GuardedEsbWrite;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Domain\Canonical\PurchaseRequestDraft;
use App\Integrations\Contracts\PurchaseRequestSource;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Models\AuditLog;

/**
 * Replace a purchase request in the ERP, on behalf of a person.
 */
class UpdatePurchaseRequest
{
    public function __construct(
        private readonly PurchaseRequestSource $purchaseRequests,
        private readonly GuardedEsbWrite $guard,
    ) {}

    /**
     * @throws WriteAlreadyInFlight when another write on this document is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(string $number, PurchaseRequestDraft $draft): void
    {
        $provider = $this->purchaseRequests->provider();

        $this->guard->run(
            PurchaseRequestLocks::document($number),
            fn () => $this->purchaseRequests->updatePurchaseRequest($number, $draft),
            fn (EsbException $exception) => UnconfirmedWrite::record($provider, 'update', $exception, [
                'number' => $number,
                'lines' => count($draft->lines),
            ]),
        );

        AuditLog::record(
            action: AuditAction::PurchaseRequestUpdated,
            metadata: [
                'provider' => $provider,
                'number' => $number,
                'lines' => count($draft->lines),
            ],
        );
    }
}
