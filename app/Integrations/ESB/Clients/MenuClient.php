<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\MenuPage;
use App\Domain\Canonical\MenuQuery;
use App\Integrations\Contracts\MenuSource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\MenuMapper;

/**
 * Adapter for the ESB Core Menu group, read-only ([ESB-012]).
 *
 * No Service wraps it: like ProductClient, it is a read that applies no
 * business rule of its own.
 */
class MenuClient implements MenuSource
{
    public const PROVIDER = 'esb';

    /**
     * The Menu group is documented under the Core base URLs.
     */
    private const FAMILY = EsbFamily::Core;

    public function __construct(
        private readonly EsbClient $client,
        private readonly MenuMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/menu-browse/get
     */
    public function listMenus(MenuQuery $query): MenuPage
    {
        $envelope = $this->client->get(
            self::FAMILY,
            '/menu/browse',
            array_filter([
                'page' => $query->page,
                'limit' => $query->limit,
                'menuName' => $query->name,
                'menuCode' => $query->code,
                // The vendor expresses "active" as 1/0.
                'flagActive' => $query->activeOnly === null ? null : (int) $query->activeOnly,
            ], static fn (mixed $value): bool => $value !== null),
            operation: 'menu.list',
        );

        return new MenuPage(
            menus: $this->mapper->fromRows($envelope->rows()),
            pagination: $envelope->pagination()?->toCanonical(),
        );
    }
}
