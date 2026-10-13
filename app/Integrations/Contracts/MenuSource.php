<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\MenuPage;
use App\Domain\Canonical\MenuQuery;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * A provider that can be asked for point-of-sale menu master data.
 * Read-only ([ESB-012]).
 *
 * App\Integrations\ESB\Clients\MenuClient is the reference implementation;
 * callers depend on this contract (ADR-016, ADR-019).
 */
interface MenuSource extends OutboundAdapter
{
    /**
     * @throws EsbException
     */
    public function listMenus(MenuQuery $query): MenuPage;
}
