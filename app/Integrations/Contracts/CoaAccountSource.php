<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\CoaAccount;
use App\Domain\Canonical\CoaAccountPage;
use App\Domain\Canonical\CoaAccountQuery;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * A provider that can be asked for its chart of accounts. Read-only
 * ([ESB-025]).
 */
interface CoaAccountSource extends OutboundAdapter
{
    /**
     * @throws EsbException
     */
    public function listAccounts(CoaAccountQuery $query): CoaAccountPage;

    /**
     * The account with this number, or null when the provider answers
     * successfully with none. Callers pass a number already held to the
     * route's pattern.
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function findAccount(string $coaNo): ?CoaAccount;
}
