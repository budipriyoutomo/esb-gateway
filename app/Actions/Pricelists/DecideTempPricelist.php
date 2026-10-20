<?php

namespace App\Actions\Pricelists;

use App\Actions\Support\DocumentWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Domain\Canonical\ApprovalDecision;
use App\Integrations\Contracts\PricelistSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Authorize or reject a temporary pricelist in the ERP, on behalf of a
 * person ([ESB-027], S2). The write and audit rules are DocumentWrites'.
 */
class DecideTempPricelist
{
    public function __construct(
        private readonly PricelistSource $pricelists,
        private readonly DocumentWrites $writes,
    ) {}

    /**
     * @throws WriteAlreadyInFlight when another write on this pricelist is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(string $number, ApprovalDecision $decision): void
    {
        $this->writes->decide(
            TempPricelistDocument::approvable(), $this->pricelists->provider(), $number, $decision, null,
            fn () => $this->pricelists->decideTempPricelist($number, $decision),
        );
    }
}
