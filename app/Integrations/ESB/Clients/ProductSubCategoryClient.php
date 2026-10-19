<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\ProductSubCategory;
use App\Domain\Canonical\ProductSubCategoryPage;
use App\Domain\Canonical\ProductSubCategoryQuery;
use App\Integrations\Contracts\ProductSubCategorySource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\ProductSubCategoryMapper;

/**
 * Adapter for ESB Core product sub-categories, read-only ([ESB-022]).
 */
class ProductSubCategoryClient implements ProductSubCategorySource
{
    public const PROVIDER = 'esb';

    private const FAMILY = EsbFamily::Core;

    private const PATH = '/product/sub-category';

    public function __construct(
        private readonly EsbClient $client,
        private readonly ProductSubCategoryMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/product-sub-category/get
     */
    public function listSubCategories(ProductSubCategoryQuery $query): ProductSubCategoryPage
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
            operation: 'product-sub-category.list',
        );

        return new ProductSubCategoryPage(
            subCategories: $this->mapper->summariesFromRows($envelope->rows()),
            pagination: $envelope->pagination()?->toCanonical(),
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/product-sub-category-subCategoryID/get
     */
    public function findSubCategory(int $id): ?ProductSubCategory
    {
        $envelope = $this->client->get(self::FAMILY, self::PATH.'/'.$id, operation: 'product-sub-category.view');

        return $this->mapper->fromDetail($envelope->resultArray());
    }
}
