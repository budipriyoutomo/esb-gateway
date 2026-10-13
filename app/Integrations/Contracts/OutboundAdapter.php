<?php

namespace App\Integrations\Contracts;

/**
 * The gateway calls out to a provider through an outbound adapter.
 *
 * An adapter owns exactly one provider's contract: its authentication, HTTP
 * transport, request and response mapping, retry behaviour, timeouts, and error
 * normalization (docs/02-architecture.md). It never calls another adapter and
 * never contains another provider's contract; cross-provider translation goes
 * through App\Domain\Canonical instead (ADR-016).
 *
 * This base interface deliberately carries only the identity every adapter
 * needs. Capabilities are declared by the narrower contracts an adapter also
 * implements -- ProductSource is the first -- rather than by one wide interface
 * that every provider would have to stub out.
 */
interface OutboundAdapter
{
    /**
     * A stable machine key for the provider, e.g. `esb`.
     *
     * Used to label canonical records with their origin, and to name the
     * provider in logs. It is not a display name and must not be localized.
     */
    public function provider(): string;
}
