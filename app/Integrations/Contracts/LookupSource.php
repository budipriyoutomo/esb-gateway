<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\LookupKind;
use App\Domain\Canonical\LookupPage;
use App\Domain\Canonical\LookupQuery;

/**
 * Reference lists for searchable pickers ([ESB-019]).
 */
interface LookupSource extends OutboundAdapter
{
    /**
     * Callers check LookupKind::requiresBranch() first; the adapter may
     * assume a branch is given when it is required.
     */
    public function lookup(LookupKind $kind, LookupQuery $query): LookupPage;
}
