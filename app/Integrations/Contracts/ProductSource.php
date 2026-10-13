<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\ProductPage;
use App\Domain\Canonical\ProductQuery;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * A provider that can be asked for product master data.
 *
 * Extracted from the working ESB implementation rather than designed in the
 * abstract, per ADR-017: App\Integrations\ESB\Clients\ProductClient is the
 * reference implementation, and callers depend on this contract so that a
 * second provider is a binding change rather than a rewrite.
 *
 * Nothing provider-shaped crosses this boundary in either direction -- the
 * vendor's parameter spelling and response envelope stay behind the adapter.
 */
interface ProductSource extends OutboundAdapter
{
    /**
     * @throws EsbException
     *
     * TBD (ADR-019): the thrown type is still the ESB's. Normalizing it to a
     * provider-neutral exception needs a second provider's error model to
     * design against; EsbExceptionRenderer maps the current hierarchy onto HTTP
     * statuses today.
     */
    public function listProducts(ProductQuery $query): ProductPage;
}
