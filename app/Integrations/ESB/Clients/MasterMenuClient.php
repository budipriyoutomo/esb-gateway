<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\MasterMenu;
use App\Domain\Canonical\MasterMenuPage;
use App\Domain\Canonical\MasterMenuQuery;
use App\Integrations\Contracts\MasterMenuSource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\MasterMenuMapper;

/**
 * Adapter for ESB OMS Master Menu, read-only ([ESB-013]).
 *
 * No Service wraps it: like ProductClient, it is a read that applies no
 * business rule of its own.
 */
class MasterMenuClient implements MasterMenuSource
{
    public const PROVIDER = 'esb';

    /**
     * The vendor documents Master Menu on the FnB backend hosts.
     */
    private const FAMILY = EsbFamily::FnbBackend;

    private const PATH = '/corev1/master/get-menu';

    public function __construct(
        private readonly EsbClient $client,
        private readonly MasterMenuMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-oms/#api-Master_Menu-get-menu
     */
    public function listMenus(MasterMenuQuery $query): MasterMenuPage
    {
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH,
            array_filter([
                'page' => $query->page,
                'menuCode' => $query->code,
            ], static fn (mixed $value): bool => $value !== null),
            operation: 'master-menu.list',
        );

        return new MasterMenuPage(
            menus: $this->mapper->fromRows($envelope->rows()),
            pagination: $envelope->pagination()?->toCanonical(),
        );
    }

    /**
     * The vendor documents no detail operation, so this reads the list
     * filtered by code. Whether that filter matches exactly or by prefix is
     * undocumented, so only a row whose code is exactly the one asked for is
     * returned.
     *
     * @throws EsbException
     */
    public function findMenu(string $code): ?MasterMenu
    {
        foreach ($this->listMenus(new MasterMenuQuery(code: $code))->menus as $menu) {
            if ($menu->code === $code) {
                return $menu;
            }
        }

        return null;
    }
}
