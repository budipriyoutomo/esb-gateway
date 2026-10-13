<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\MasterMenu;
use App\Domain\Canonical\MasterMenuPage;
use App\Domain\Canonical\MasterMenuQuery;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * A provider that can be asked for point-of-sale menu master data. Read-only
 * for now; create and update are not built ([ESB-013]).
 *
 * App\Integrations\ESB\Clients\MasterMenuClient is the reference implementation;
 * callers depend on this contract (ADR-016, ADR-019).
 */
interface MasterMenuSource extends OutboundAdapter
{
    /**
     * @throws EsbException
     */
    public function listMenus(MasterMenuQuery $query): MasterMenuPage;

    /**
     * The menu with exactly this code, or null when the provider has none.
     *
     * @param  string  $code  A menu code already validated by the caller.
     *
     * @throws EsbException
     */
    public function findMenu(string $code): ?MasterMenu;
}
