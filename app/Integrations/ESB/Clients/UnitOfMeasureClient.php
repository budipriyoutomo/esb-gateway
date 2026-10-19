<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\UnitOfMeasure;
use App\Domain\Canonical\UnitOfMeasurePage;
use App\Domain\Canonical\UnitOfMeasureQuery;
use App\Integrations\Contracts\UnitOfMeasureSource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\UnitOfMeasureMapper;

/**
 * Adapter for ESB Core units, read-only ([ESB-020]).
 */
class UnitOfMeasureClient implements UnitOfMeasureSource
{
    public const PROVIDER = 'esb';

    private const FAMILY = EsbFamily::Core;

    private const PATH = '/units';

    public function __construct(
        private readonly EsbClient $client,
        private readonly UnitOfMeasureMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/units/get
     */
    public function listUnits(UnitOfMeasureQuery $query): UnitOfMeasurePage
    {
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH,
            array_filter([
                'page' => $query->page,
                'limit' => $query->limit,
                'indexSearch' => $query->search,
                'flagActive' => $query->activeOnly === null ? null : (int) $query->activeOnly,
            ], static fn (mixed $value): bool => $value !== null),
            operation: 'unit.list',
        );

        return new UnitOfMeasurePage(
            units: $this->mapper->summariesFromRows($envelope->rows()),
            pagination: $envelope->pagination()?->toCanonical(),
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/units-uomID/get
     */
    public function findUnit(int $id): ?UnitOfMeasure
    {
        $envelope = $this->client->get(self::FAMILY, self::PATH.'/'.$id, operation: 'unit.view');

        return $this->mapper->fromDetail($envelope->resultArray());
    }
}
