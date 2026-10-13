<?php

namespace App\Http\Requests\Products;

use App\Authorization\Permission;
use App\Domain\Canonical\ProductQuery;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the product list filters before they reach the ESB.
 *
 * The ESB does validate these itself, but answers a bad `sort` or a
 * non-numeric `page` with EC03100032 "Unknown Error" and a raw Go strconv
 * message. Catching them here turns an opaque upstream failure into a field
 * error, and saves a round trip.
 */
class ListProductsRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::InvokeEsbOperations->value) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.ProductQuery::MAX_LIMIT],
            'productName' => ['sometimes', 'string', 'max:255'],
            'productCode' => ['sometimes', 'string', 'max:255'],
            'categoryID' => ['sometimes', 'integer', 'min:1'],
            'subCategoryID' => ['sometimes', 'integer', 'min:1'],
            'categoryTypeID' => ['sometimes', 'integer', 'min:1'],
            'bomName' => ['sometimes', 'string', 'max:255'],
            'sort' => ['sometimes', 'string', Rule::in(ProductQuery::SORTABLE)],
            'flagActive' => ['sometimes', 'integer', Rule::in([0, 1])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'limit.max' => 'The ERP service accepts at most :max records per page.',
            'sort.in' => 'Sort must be one of: '.implode(', ', ProductQuery::SORTABLE).'.',
        ];
    }

    public function toQuery(): ProductQuery
    {
        $flagActive = $this->validatedInt('flagActive');

        return new ProductQuery(
            page: (int) ($this->validated('page') ?? 1),
            limit: (int) ($this->validated('limit') ?? ProductQuery::DEFAULT_LIMIT),
            name: $this->validatedString('productName'),
            code: $this->validatedString('productCode'),
            categoryId: $this->validatedInt('categoryID'),
            subCategoryId: $this->validatedInt('subCategoryID'),
            categoryTypeId: $this->validatedInt('categoryTypeID'),
            bomName: $this->validatedString('bomName'),
            sort: $this->validatedString('sort'),
            activeOnly: $flagActive === null ? null : $flagActive === 1,
        );
    }

    /**
     * The filters echoed back to the page, in the query-string spelling the
     * frontend already sends. These are the request's own parameter names, not
     * the ESB's -- the two happen to coincide because the ESB was the first
     * provider, and the page must keep working if that stops being true.
     *
     * @return array<string, int|string>
     */
    public function validatedFilters(): array
    {
        $query = $this->toQuery();

        return array_filter([
            'page' => $query->page,
            'limit' => $query->limit,
            'productName' => $query->name,
            'productCode' => $query->code,
            'categoryID' => $query->categoryId,
            'subCategoryID' => $query->subCategoryId,
            'categoryTypeID' => $query->categoryTypeId,
            'bomName' => $query->bomName,
            'sort' => $query->sort,
            'flagActive' => $query->activeOnly === null ? null : (int) $query->activeOnly,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
