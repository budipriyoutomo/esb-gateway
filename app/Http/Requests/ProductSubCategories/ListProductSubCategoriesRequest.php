<?php

namespace App\Http\Requests\ProductSubCategories;

use App\Domain\Canonical\ProductSubCategoryQuery;
use App\Http\Requests\ListReferenceDataRequest;

/**
 * Validates the product sub-category list filters before they reach the ESB
 * ([ESB-022]).
 */
class ListProductSubCategoriesRequest extends ListReferenceDataRequest
{
    public function toQuery(): ProductSubCategoryQuery
    {
        return new ProductSubCategoryQuery(
            page: $this->page(),
            limit: $this->limit(),
            search: $this->search(),
            activeOnly: $this->activeOnly(),
        );
    }
}
