<?php

namespace App\Actions\PurchaseOrders;

use App\Actions\Support\GuardedEsbWrite;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Domain\Canonical\PurchaseOrderDecision;
use App\Integrations\Contracts\PurchaseOrderSource;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Models\AuditLog;

/**
 * Authorize or reject a purchase order in the ERP, on behalf of a person.
 *
 * The write rules are GuardedEsbWrite's. The ERP records the gateway's shared
 * service credential (ADR-009) as the approver, so the audit row is the only
 * record of the actual person.
 */
class DecidePurchaseOrder
{
    public function __construct(
        private readonly PurchaseOrderSource $purchaseOrders,
        private readonly GuardedEsbWrite $guard,
    ) {}

    /**
     * @param  string|null  $reason  Required for a rejection; dropped for an
     *                               authorization.
     *
     * @throws WriteAlreadyInFlight when another write on this document is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(string $number, PurchaseOrderDecision $decision, ?string $reason = null): void
    {
        $provider = $this->purchaseOrders->provider();
        $reason = $decision === PurchaseOrderDecision::Reject ? $reason : null;

        $this->guard->run(
            PurchaseOrderLocks::document($number),
            fn () => $this->purchaseOrders->decidePurchaseOrder($number, $decision, $reason),
            fn (EsbException $exception) => UnconfirmedWrite::record($provider, $decision->value, $number, $exception),
        );

        AuditLog::record(
            action: match ($decision) {
                PurchaseOrderDecision::Authorize => AuditAction::PurchaseOrderAuthorized,
                PurchaseOrderDecision::Reject => AuditAction::PurchaseOrderRejected,
            },
            metadata: array_filter([
                'provider' => $provider,
                'number' => $number,
                'reason' => $reason,
            ], static fn (?string $value): bool => $value !== null),
        );
    }
}
