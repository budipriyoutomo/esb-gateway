<?php

namespace App\Actions\SimplePurchases;

use App\Actions\Support\GuardedEsbWrite;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Domain\Canonical\SimplePurchaseDecision;
use App\Integrations\Contracts\SimplePurchaseSource;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Models\AuditLog;

/**
 * Authorize or reject a simple purchase in the ERP, on behalf of a person.
 *
 * The write rules are GuardedEsbWrite's. The ERP records the gateway's shared
 * service credential (ADR-009) as the approver, so the audit row is the only
 * record of the actual person.
 */
class DecideSimplePurchase
{
    public function __construct(
        private readonly SimplePurchaseSource $simplePurchases,
        private readonly GuardedEsbWrite $guard,
    ) {}

    /**
     * @param  string|null  $reason  Required for a rejection; dropped for an
     *                               authorization.
     *
     * @throws WriteAlreadyInFlight when another write on this document is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(string $number, SimplePurchaseDecision $decision, ?string $reason = null): void
    {
        $provider = $this->simplePurchases->provider();
        $reason = $decision === SimplePurchaseDecision::Reject ? $reason : null;

        $this->guard->run(
            SimplePurchaseLocks::document($number),
            fn () => $this->simplePurchases->decideSimplePurchase($number, $decision, $reason),
            fn (EsbException $exception) => UnconfirmedWrite::record($provider, $decision->value, $number, $exception),
        );

        AuditLog::record(
            action: match ($decision) {
                SimplePurchaseDecision::Authorize => AuditAction::SimplePurchaseAuthorized,
                SimplePurchaseDecision::Reject => AuditAction::SimplePurchaseRejected,
            },
            metadata: array_filter([
                'provider' => $provider,
                'number' => $number,
                'reason' => $reason,
            ], static fn (?string $value): bool => $value !== null),
        );
    }
}
