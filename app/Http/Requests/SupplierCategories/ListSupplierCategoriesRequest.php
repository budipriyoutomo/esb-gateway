<?php

namespace App\Http\Requests\SupplierCategories;

use App\Domain\Canonical\SupplierCategoryQuery;
use App\Http\Requests\ListReferenceDataRequest;

/**
 * Validates the supplier category list filters before they reach the ESB
 * ([ESB-023]).
 */
class ListSupplierCategoriesRequest extends ListReferenceDataRequest
{
    public function toQuery(): SupplierCategoryQuery
    {
        return new SupplierCategoryQuery(
            page: $this->page(),
            limit: $this->limit(),
            search: $this->search(),
            activeOnly: $this->activeOnly(),
        );
    }
}
