<?php

namespace App\Actions\PurchaseRequests;

use RuntimeException;

/**
 * The period holds more open purchase orders than one PR outstanding report
 * reads in detail (ADR-034). Nothing was read beyond the order lists.
 */
class TooManyOrdersForReport extends RuntimeException
{
    public function __construct(public readonly int $count)
    {
        parent::__construct(sprintf(
            'This period has %d open purchase orders; the report reads at most %d. Narrow the required dates or choose a branch.',
            $count,
            BuildOutstandingRequestReport::MAX_ORDERS,
        ));
    }
}
