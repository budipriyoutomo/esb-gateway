<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\Purpose;
use App\Domain\Canonical\PurposePage;
use App\Domain\Canonical\PurposeQuery;
use App\Integrations\Contracts\PurposeSource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\PurposeMapper;

/**
 * Adapter for ESB Core Purpose, read-only ([ESB-015]).
 *
 * No Service wraps it: like ProductClient, it is a read that applies no
 * business rule of its own.
 */
class PurposeClient implements PurposeSource
{
    public const PROVIDER = 'esb';

    /**
     * The Purpose group is documented under the Core base URLs.
     */
    private const FAMILY = EsbFamily::Core;

    private const PATH = '/purpose';

    public function __construct(
        private readonly EsbClient $client,
        private readonly PurposeMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/purpose/get
     */
    public function listPurposes(PurposeQuery $query): PurposePage
    {
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH,
            array_filter([
                'page' => $query->page,
                'limit' => $query->limit,
                'purposeName' => $query->name,
                'purposeCoaNo' => $query->accountNo,
                // The vendor expresses "active" as 1/0 in the query, though
                // rows carry a boolean.
                'flagActive' => $query->activeOnly === null ? null : (int) $query->activeOnly,
            ], static fn (mixed $value): bool => $value !== null),
            operation: 'purpose.list',
        );

        return new PurposePage(
            purposes: $this->mapper->summariesFromRows($envelope->rows()),
            pagination: $envelope->pagination()?->toCanonical(),
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/purpose-purposeID/get
     */
    public function findPurpose(int $id): ?Purpose
    {
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH.'/'.$id,
            operation: 'purpose.view',
        );

        return $this->mapper->fromDetail($envelope->resultArray());
    }
}
