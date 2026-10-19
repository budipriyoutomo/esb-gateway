<?php

namespace App\Actions\SimplePurchases;

use App\Actions\Support\DocumentWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Integrations\Contracts\SimplePurchaseSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Delete a simple purchase in the ERP, on behalf of a person.
 *
 * The write and audit rules are DocumentWrites'.
 */
class DeleteSimplePurchase
{
    public function __construct(
        private readonly SimplePurchaseSource $simplePurchases,
        private readonly DocumentWrites $writes,
    ) {}

    /**
     * @throws WriteAlreadyInFlight when another write on this document is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(string $number): void
    {
        $this->writes->delete(
            SimplePurchaseDocument::approvable(), $this->simplePurchases->provider(), $number,
            fn () => $this->simplePurchases->deleteSimplePurchase($number),
        );
    }
}
