<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\ProductCategory;
use App\Domain\Canonical\ProductCategoryPage;
use App\Domain\Canonical\ProductCategoryQuery;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * A provider that can be asked for product categories. Read-only ([ESB-021]).
 */
interface ProductCategorySource extends OutboundAdapter
{
    /**
     * @throws EsbException
     */
    public function listCategories(ProductCategoryQuery $query): ProductCategoryPage;

    /**
     * The category with this id, or null when the provider answers
     * successfully with none.
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function findCategory(int $id): ?ProductCategory;
}
