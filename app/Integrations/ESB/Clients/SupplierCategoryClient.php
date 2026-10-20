<?php

namespace App\Integrations\ESB\Clients;

use App\Domain\Canonical\SupplierCategory;
use App\Domain\Canonical\SupplierCategoryDraft;
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

    /**
     * The vendor answers true when the category can be deleted, false or a
     * validation error otherwise. Only a literal true counts.
     *
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/supplier-category-supplierCategoryID--check-delete/get
     */
    public function canDeleteCategory(int $id): bool
    {
        $envelope = $this->client->get(self::FAMILY, self::PATH.'/'.$id.'/check-delete', operation: 'supplier-category.check-delete');

        return $envelope->result === true;
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/supplier-category-supplierCategoryID/delete
     */
    public function deleteCategory(int $id): void
    {
        $this->client->delete(self::FAMILY, self::PATH.'/'.$id, operation: 'supplier-category.delete');
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/supplier-category-supplierCategoryID--restore/patch
     */
    public function restoreCategory(int $id): void
    {
        $this->client->patch(self::FAMILY, self::PATH.'/'.$id.'/restore', operation: 'supplier-category.restore');
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/supplier-category-/post
     */
    public function createCategory(SupplierCategoryDraft $draft): ?int
    {
        $envelope = $this->client->post(self::FAMILY, self::PATH.'/', $this->mapper->requestFrom($draft), operation: 'supplier-category.create');

        return $this->mapper->createdIdFrom($envelope->resultArray());
    }

    /**
     * @throws EsbException
     *
     * @see https://developers.esb.co.id/esb-core/#/paths/supplier-category-supplierCategoryID/put
     */
    public function updateCategory(int $id, SupplierCategoryDraft $draft): void
    {
        $this->client->put(self::FAMILY, self::PATH.'/'.$id, $this->mapper->requestFrom($draft), operation: 'supplier-category.update');
    }
}
