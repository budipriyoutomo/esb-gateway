<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\ProductCategory;
use App\Domain\Canonical\ProductCategoryPage;
use App\Domain\Canonical\ProductCategoryQuery;
use App\Integrations\Contracts\ProductCategorySource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\ProductCategoryMapper;

/**
 * Adapter for ESB Core product categories, read-only ([ESB-021]).
 */
class ProductCategoryClient implements ProductCategorySource
{
    public const PROVIDER = 'esb';

    private const FAMILY = EsbFamily::Core;

    private const PATH = '/product/category';

    public function __construct(
        private readonly EsbClient $client,
        private readonly ProductCategoryMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/product-category/get
     */
    public function listCategories(ProductCategoryQuery $query): ProductCategoryPage
    {
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH,
            array_filter([
                'page' => $query->page,
                'limit' => $query->limit,
                'indexSearch' => $query->search,
                'categoryTypeID' => $query->typeId,
                'flagActive' => $query->activeOnly === null ? null : (int) $query->activeOnly,
            ], static fn (mixed $value): bool => $value !== null),
            operation: 'product-category.list',
        );

        return new ProductCategoryPage(
            categories: $this->mapper->summariesFromRows($envelope->rows()),
            pagination: $envelope->pagination()?->toCanonical(),
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/product-category-categoryID/get
     */
    public function findCategory(int $id): ?ProductCategory
    {
        $envelope = $this->client->get(self::FAMILY, self::PATH.'/'.$id, operation: 'product-category.view');

        return $this->mapper->fromDetail($envelope->resultArray());
    }
}
