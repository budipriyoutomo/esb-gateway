<?php

namespace App\Actions\SimplePurchases;

use App\Actions\Support\DocumentWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Domain\Canonical\ApprovalDecision;
use App\Integrations\Contracts\SimplePurchaseSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Authorize or reject a simple purchase in the ERP, on behalf of a person.
 *
 * The write and audit rules are DocumentWrites'.
 */
class DecideSimplePurchase
{
    public function __construct(
        private readonly SimplePurchaseSource $simplePurchases,
        private readonly DocumentWrites $writes,
    ) {}

    /**
     * @param  string|null  $reason  Required for a rejection; dropped for an
     *                               authorization.
     *
     * @throws WriteAlreadyInFlight when another write on this document is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(string $number, ApprovalDecision $decision, ?string $reason = null): void
    {
        $this->writes->decide(
            SimplePurchaseDocument::approvable(), $this->simplePurchases->provider(), $number, $decision, $reason,
            fn (?string $reason) => $this->simplePurchases->decideSimplePurchase($number, $decision, $reason),
        );
    }
}
