<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\Supplier;
use App\Domain\Canonical\SupplierPage;
use App\Domain\Canonical\SupplierQuery;
use App\Integrations\Contracts\SupplierSource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\SupplierMapper;

/**
 * Adapter for ESB Core suppliers, read-only ([ESB-024]).
 */
class SupplierClient implements SupplierSource
{
    public const PROVIDER = 'esb';

    private const FAMILY = EsbFamily::Core;

    private const PATH = '/supplier';

    public function __construct(
        private readonly EsbClient $client,
        private readonly SupplierMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/supplier/get
     */
    public function listSuppliers(SupplierQuery $query): SupplierPage
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
            operation: 'supplier.list',
        );

        return new SupplierPage(
            suppliers: $this->mapper->summariesFromRows($envelope->rows()),
            pagination: $envelope->pagination()?->toCanonical(),
        );
    }

    /**
     * Contacts and bank accounts are nested in the detail, so the separate
     * /contacts and /banks reads are not needed.
     *
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/supplier-supplierID/get
     */
    public function findSupplier(int $id): ?Supplier
    {
        $envelope = $this->client->get(self::FAMILY, self::PATH.'/'.$id, operation: 'supplier.view');

        return $this->mapper->fromDetail($envelope->resultArray());
    }
}
