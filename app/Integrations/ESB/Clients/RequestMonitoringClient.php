<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\UnorderedRequestQuery;
use App\Integrations\Contracts\RequestMonitoringSource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\RequestMonitoringMapper;

/**
 * Adapter for the ESB request monitoring list ([ESB-053], ADR-034). A POST
 * the vendor documents as a read ("List request items"), so it is retried
 * like a GET (ADR-025). No request process is sent: the report wants every
 * one but Transfer, which the caller leaves out.
 */
class RequestMonitoringClient implements RequestMonitoringSource
{
    public const PROVIDER = 'esb';

    private const FAMILY = EsbFamily::Core;

    public function __construct(
        private readonly EsbClient $client,
        private readonly RequestMonitoringMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/purchase-request-monitoring/post
     */
    public function unorderedItems(UnorderedRequestQuery $query): array
    {
        $envelope = $this->client->postRead(
            self::FAMILY,
            '/purchase/request-monitoring',
            array_filter([
                'branchIDs' => $query->branchIds,
                'requiredDateFrom' => $query->requiredDateFrom,
                'requiredDateTo' => $query->requiredDateTo,
                'purchaseRequestNum' => $query->purchaseRequestNumber,
            ], static fn (mixed $value): bool => $value !== null),
            operation: 'request-monitoring.list',
        );

        return $this->mapper->itemsFromRows($envelope->rows());
    }
}
