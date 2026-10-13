<?php

namespace App\Actions\PurchaseRequests;

use App\Actions\Support\GuardedEsbWrite;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Domain\Canonical\PurchaseRequestDecision;
use App\Integrations\Contracts\PurchaseRequestSource;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Models\AuditLog;

/**
 * Authorize or reject a purchase request in the ERP, on behalf of a person.
 *
 * The write rules (one at a time per document, one attempt, audit an
 * ambiguous failure) are GuardedEsbWrite's. What is specific here: the ERP
 * records the gateway's shared service credential (ADR-009) as the approver,
 * so the audit row is the only record of the actual person.
 */
class DecidePurchaseRequest
{
    public function __construct(
        private readonly PurchaseRequestSource $purchaseRequests,
        private readonly GuardedEsbWrite $guard,
    ) {}

    /**
     * @throws WriteAlreadyInFlight when another write on this document is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(string $number, PurchaseRequestDecision $decision): void
    {
        $this->guard->run(
            PurchaseRequestLocks::document($number),
            fn () => $this->purchaseRequests->decidePurchaseRequest($number, $decision),
            function (EsbException $exception) use ($number, $decision): void {
                AuditLog::record(
                    action: AuditAction::PurchaseRequestDecisionUnconfirmed,
                    metadata: [
                        'provider' => $this->purchaseRequests->provider(),
                        'number' => $number,
                        'decision' => $decision->value,
                        'failure' => $exception->kind(),
                        'correlation_id' => $exception->context->correlationId,
                    ],
                );
            },
        );

        AuditLog::record(
            action: match ($decision) {
                PurchaseRequestDecision::Authorize => AuditAction::PurchaseRequestAuthorized,
                PurchaseRequestDecision::Reject => AuditAction::PurchaseRequestRejected,
            },
            metadata: [
                'provider' => $this->purchaseRequests->provider(),
                'number' => $number,
            ],
        );
    }
}
