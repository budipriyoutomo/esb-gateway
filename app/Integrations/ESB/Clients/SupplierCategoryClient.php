<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\SupplierCategory;
use App\Domain\Canonical\SupplierCategoryPage;
use App\Domain\Canonical\SupplierCategoryQuery;
use App\Integrations\Contracts\SupplierCategorySource;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Mappers\SupplierCategoryMapper;

/**
 * Adapter for ESB Core supplier categories, read-only ([ESB-023]).
 *
 * The paginated list is /supplier/category/list; /supplier/category itself
 * returns every row unpaginated and is not used.
 */
class SupplierCategoryClient implements SupplierCategorySource
{
    public const PROVIDER = 'esb';

    private const FAMILY = EsbFamily::Core;

    private const PATH = '/supplier/category';

    public function __construct(
        private readonly EsbClient $client,
        private readonly SupplierCategoryMapper $mapper,
    ) {}

    public function provider(): string
    {
        return self::PROVIDER;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/supplier-category-list/get
     */
    public function listCategories(SupplierCategoryQuery $query): SupplierCategoryPage
    {
        $envelope = $this->client->get(
            self::FAMILY,
            self::PATH.'/list',
            array_filter([
                'page' => $query->page,
                'limit' => $query->limit,
                'supplierCategoryName' => $query->search,
                'flagActive' => $query->activeOnly === null ? null : (int) $query->activeOnly,
            ], static fn (mixed $value): bool => $value !== null),
            operation: 'supplier-category.list',
        );

        return new SupplierCategoryPage(
            categories: $this->mapper->summariesFromRows($envelope->rows()),
            pagination: $envelope->pagination()?->toCanonical(),
        );
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/supplier-category-supplierCategoryID/get
     */
    public function findCategory(int $id): ?SupplierCategory
    {
        $envelope = $this->client->get(self::FAMILY, self::PATH.'/'.$id, operation: 'supplier-category.view');

        return $this->mapper->fromDetail($envelope->resultArray());
    }
}
