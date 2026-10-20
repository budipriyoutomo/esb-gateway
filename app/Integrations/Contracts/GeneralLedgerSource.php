<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\LedgerEntryPage;
use App\Domain\Canonical\LedgerQuery;
use App\Domain\Canonical\LedgerSummaryPage;
use App\Domain\Canonical\LedgerSummaryQuery;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Reads the general ledger ([ESB-042]).
 */
interface GeneralLedgerSource extends OutboundAdapter
{
    /**
     * One page of an account's entries over a period.
     *
     * @throws EsbException
     */
    public function entries(LedgerQuery $query): LedgerEntryPage;

    /**
     * @throws EsbException
     */
    public function summary(LedgerSummaryQuery $query): LedgerSummaryPage;
}
