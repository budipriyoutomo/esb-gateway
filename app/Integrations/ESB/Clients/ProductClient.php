<?php

namespace App\Integrations\ESB\Clients;

use App\Integrations\ESB\DTOs\ProductListQuery;
use App\Integrations\ESB\DTOs\ProductListResult;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\ProductMapper;

/**
 * Adapter for the ESB Master_Product operations.
 *
 * This is the dedicated integration class AGENT.md requires: it owns the
 * request and response mapping for one endpoint group, while EsbClient owns
 * transport, authentication, retry, and error normalization.
 *
 * There is deliberately no Service or Action wrapping this. For a read that
 * applies no business rule of its own, another layer would be the empty
 * abstraction docs/02-architecture.md warns against. Add one here when a
 * product operation gains logic worth separating.
 */
class ProductClient
{
    /**
     * The Master_Product group is documented under the Core base URLs.
     */
    private const FAMILY = EsbFamily::Core;

    public function __construct(
        private readonly EsbClient $client,
        private readonly ProductMapper $mapper,
    ) {}

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#api-Master_Product-product-list
     */
    public function list(ProductListQuery $query): ProductListResult
    {
        $envelope = $this->client->get(
            self::FAMILY,
            '/product/list',
            $query->toQueryParameters(),
            operation: 'product.list',
        );

        return new ProductListResult(
            products: $this->mapper->fromRows($envelope->rows()),
            pagination: $envelope->pagination(),
        );
    }
}
