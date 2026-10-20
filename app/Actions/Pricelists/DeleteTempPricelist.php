<?php

namespace App\Actions\Pricelists;

use App\Actions\Support\DocumentWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Integrations\Contracts\PricelistSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Delete a temporary pricelist in the ERP, on behalf of a person
 * ([ESB-027], S2). The write and audit rules are DocumentWrites'.
 */
class DeleteTempPricelist
{
    public function __construct(
        private readonly PricelistSource $pricelists,
        private readonly DocumentWrites $writes,
    ) {}

    /**
     * @throws WriteAlreadyInFlight when another write on this pricelist is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(string $number): void
    {
        $this->writes->delete(
            TempPricelistDocument::approvable(), $this->pricelists->provider(), $number,
            fn () => $this->pricelists->deleteTempPricelist($number),
        );
    }
}
