<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\SalesDataReceipt;
use App\Integrations\ESB\Exceptions\EsbConfigurationException;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * A provider that accepts point-of-sale transactions.
 *
 * The body is the provider's own shape: POS clients send the ESB's Push Sales
 * Data V2 body to the gateway, and the gateway forwards it unchanged
 * (ADR-022). There is no canonical sales model yet; one needs a second
 * provider to design against (ADR-017).
 *
 * The ESB overwrites a transaction pushed again with the same salesNum, so a
 * push may be repeated. Implementations still make exactly one attempt per
 * call; whether to repeat is the caller's decision.
 *
 * App\Integrations\ESB\Clients\SalesDataClient is the reference
 * implementation.
 */
interface SalesDataSink extends OutboundAdapter
{
    /**
     * @param  array<string, mixed>  $salesData  A `{"salesHead": {...}}` body.
     *
     * @throws EsbException
     * @throws EsbConfigurationException when the provider cannot be reached as
     *                                   configured, e.g. its family is disabled
     */
    public function pushSalesData(array $salesData): SalesDataReceipt;
}
