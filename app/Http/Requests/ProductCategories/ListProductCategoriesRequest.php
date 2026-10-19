<?php

namespace App\Http\Requests\ProductCategories;

use App\Domain\Canonical\ProductCategoryQuery;
use App\Http\Requests\ListReferenceDataRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the product category list filters before they reach the ESB
 * ([ESB-021]).
 */
class ListProductCategoriesRequest extends ListReferenceDataRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    protected function extraRules(): array
    {
        return [
            'categoryTypeId' => ['sometimes', 'nullable', 'integer', Rule::in(array_keys(ProductCategoryQuery::TYPES))],
        ];
    }

    /**
     * @return array<string, int|string|null>
     */
    protected function extraFilters(): array
    {
        return ['categoryTypeId' => $this->validatedInt('categoryTypeId')];
    }

    public function toQuery(): ProductCategoryQuery
    {
        return new ProductCategoryQuery(
            page: $this->page(),
            limit: $this->limit(),
            search: $this->search(),
            typeId: $this->validatedInt('categoryTypeId'),
            activeOnly: $this->activeOnly(),
        );
    }
}
