<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\UnorderedRequestItem;
use App\Domain\Canonical\UnorderedRequestQuery;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * A provider that can say which purchase request items are not yet ordered
 * ([ESB-053], ADR-034). Read-only; the vendor's "process into purchase /
 * transfer" operations are not here.
 *
 * App\Integrations\ESB\Clients\RequestMonitoringClient is the reference
 * implementation; callers depend on this contract (ADR-016, ADR-019).
 */
interface RequestMonitoringSource extends OutboundAdapter
{
    /**
     * Every matching item: the vendor does not page this read.
     *
     * @return list<UnorderedRequestItem>
     *
     * @throws EsbException
     */
    public function unorderedItems(UnorderedRequestQuery $query): array;
}
