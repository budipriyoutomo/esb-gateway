<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\BillOfMaterial;
use App\Domain\Canonical\BillOfMaterialPage;
use App\Domain\Canonical\BillOfMaterialQuery;
use App\Integrations\Contracts\BillOfMaterialSource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\BillOfMaterialMapper;

/**
 * Adapter for ESB Core Master Bill of Material, read-only ([ESB-014]).
 *
 * No Service wraps it: like ProductClient, it is a read that applies no
 * business rule of its own.
 */
class BillOfMaterialClient implements BillOfMaterialSource
{
    public const PROVIDER = 'esb';

    /**
     * The Master Bill of Material group is documented under the Core base URLs.
     */
    private const FAMILY = EsbFamily::Core;

    private const PATH = '/product/bom';

    public function __construct(
        private readonly EsbClient $client,
        private readonly BillOfMaterialMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/product-bom/get
     */
    public function listBillsOfMaterial(BillOfMaterialQuery $query): BillOfMaterialPage
    {
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH,
            array_filter([
                'page' => $query->page,
                'limit' => $query->limit,
                'bomName' => $query->name,
                'bomCode' => $query->code,
                'productName' => $query->productName,
                // The vendor expresses "active" as 1/0, not as a boolean.
                'flagActive' => $query->activeOnly === null ? null : (int) $query->activeOnly,
            ], static fn (mixed $value): bool => $value !== null),
            operation: 'bom.list',
        );

        return new BillOfMaterialPage(
            boms: $this->mapper->summariesFromRows($envelope->rows()),
            pagination: $envelope->pagination()?->toCanonical(),
        );
    }

    /**
     * The optional `locationID` scope is not sent: the gateway has no
     * location picker yet, and without it the ESB returns the BOM unscoped.
     *
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/product-bom-bomID/get
     */
    public function findBillOfMaterial(int $id): ?BillOfMaterial
    {
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH.'/'.$id,
            operation: 'bom.view',
        );

        return $this->mapper->fromDetail($envelope->resultArray());
    }
}
