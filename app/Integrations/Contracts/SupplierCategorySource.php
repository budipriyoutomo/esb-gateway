<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\SupplierCategory;
use App\Domain\Canonical\SupplierCategoryPage;
use App\Domain\Canonical\SupplierCategoryQuery;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * A provider that can be asked for supplier categories. Read-only
 * ([ESB-023]).
 */
interface SupplierCategorySource extends OutboundAdapter
{
    /**
     * @throws EsbException
     */
    public function listCategories(SupplierCategoryQuery $query): SupplierCategoryPage;

    /**
     * The category with this id, or null when the provider answers
     * successfully with none.
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function findCategory(int $id): ?SupplierCategory;
}
