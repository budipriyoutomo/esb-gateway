<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\ProductPage;
use App\Domain\Canonical\ProductQuery;
use App\Integrations\Contracts\ProductSource;
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
 * It is also the reference implementation of ProductSource (ADR-016/ADR-017):
 * the contract was extracted from this class, not designed ahead of it, and the
 * vendor's parameter spelling stops here.
 *
 * There is deliberately no Service or Action wrapping this. For a read that
 * applies no business rule of its own, another layer would be the empty
 * abstraction docs/02-architecture.md warns against. Add one here when a
 * product operation gains logic worth separating.
 */
class ProductClient implements ProductSource
{
    /**
     * The provider key stamped onto every canonical record this adapter emits.
     */
    public const PROVIDER = 'esb';

    /**
     * The Master_Product group is documented under the Core base URLs.
     */
    private const FAMILY = EsbFamily::Core;

    public function __construct(
        private readonly EsbClient $client,
        private readonly ProductMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#api-Master_Product-product-list
     */
    public function listProducts(ProductQuery $query): ProductPage
    {
        $envelope = $this->client->get(
            self::FAMILY,
            '/product/list',
            $this->toQueryParameters($query),
            operation: 'product.list',
        );

        return new ProductPage(
            products: $this->mapper->fromRows($envelope->rows()),
            pagination: $envelope->pagination()?->toCanonical(),
        );
    }

    /**
     * Translate the canonical filters into the vendor's parameter names.
     *
     * Anything null is omitted: sending an empty filter would make the ESB
     * narrow the result set to rows matching an empty string.
     *
     * @return array<string, int|string>
     */
    private function toQueryParameters(ProductQuery $query): array
    {
        return array_filter([
            'page' => $query->page,
            'limit' => $query->limit,
            'productName' => $query->name,
            'productCode' => $query->code,
            'categoryID' => $query->categoryId,
            'subCategoryID' => $query->subCategoryId,
            'categoryTypeID' => $query->categoryTypeId,
            'bomName' => $query->bomName,
            'sort' => $query->sort,
            // The vendor expresses "active" as 1/0, not as a boolean.
            'flagActive' => $query->activeOnly === null ? null : (int) $query->activeOnly,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
