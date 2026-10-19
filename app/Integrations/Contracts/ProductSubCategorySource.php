<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\ProductSubCategory;
use App\Domain\Canonical\ProductSubCategoryPage;
use App\Domain\Canonical\ProductSubCategoryQuery;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * A provider that can be asked for product sub-categories. Read-only
 * ([ESB-022]).
 */
interface ProductSubCategorySource extends OutboundAdapter
{
    /**
     * @throws EsbException
     */
    public function listSubCategories(ProductSubCategoryQuery $query): ProductSubCategoryPage;

    /**
     * The sub-category with this id, or null when the provider answers
     * successfully with none.
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function findSubCategory(int $id): ?ProductSubCategory;
}
