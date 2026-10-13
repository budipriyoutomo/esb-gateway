<?php

namespace App\Actions\PurchaseInvoices;

use App\Actions\Support\GuardedEsbWrite;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Domain\Canonical\PurchaseInvoiceDecision;
use App\Integrations\Contracts\PurchaseInvoiceSource;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Models\AuditLog;

/**
 * Authorize or reject a purchase invoice in the ERP, on behalf of a person.
 *
 * The write rules are GuardedEsbWrite's. The ERP records the gateway's shared
 * service credential (ADR-009) as the approver, so the audit row is the only
 * record of the actual person.
 */
class DecidePurchaseInvoice
{
    public function __construct(
        private readonly PurchaseInvoiceSource $purchaseInvoices,
        private readonly GuardedEsbWrite $guard,
    ) {}

    /**
     * @throws WriteAlreadyInFlight when another write on this document is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(string $number, PurchaseInvoiceDecision $decision): void
    {
        $provider = $this->purchaseInvoices->provider();

        $this->guard->run(
            PurchaseInvoiceLocks::document($number),
            fn () => $this->purchaseInvoices->decidePurchaseInvoice($number, $decision),
            fn (EsbException $exception) => UnconfirmedWrite::record($provider, $decision->value, $number, $exception),
        );

        AuditLog::record(
            action: match ($decision) {
                PurchaseInvoiceDecision::Authorize => AuditAction::PurchaseInvoiceAuthorized,
                PurchaseInvoiceDecision::Reject => AuditAction::PurchaseInvoiceRejected,
            },
            metadata: [
                'provider' => $provider,
                'number' => $number,
            ],
        );
    }
}
