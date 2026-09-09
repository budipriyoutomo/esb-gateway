<?php

namespace App\Http\Requests\Products;

use App\Authorization\Permission;
use App\Integrations\ESB\DTOs\ProductListQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the product list filters before they reach the ESB.
 *
 * The ESB does validate these itself, but answers a bad `sort` or a
 * non-numeric `page` with EC03100032 "Unknown Error" and a raw Go strconv
 * message. Catching them here turns an opaque upstream failure into a field
 * error, and saves a round trip.
 */
class ListProductsRequest extends FormRequest
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
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.ProductListQuery::MAX_LIMIT],
            'productName' => ['sometimes', 'string', 'max:255'],
            'productCode' => ['sometimes', 'string', 'max:255'],
            'categoryID' => ['sometimes', 'integer', 'min:1'],
            'subCategoryID' => ['sometimes', 'integer', 'min:1'],
            'categoryTypeID' => ['sometimes', 'integer', 'min:1'],
            'bomName' => ['sometimes', 'string', 'max:255'],
            'sort' => ['sometimes', 'string', Rule::in(ProductListQuery::SORTABLE)],
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
            'sort.in' => 'Sort must be one of: '.implode(', ', ProductListQuery::SORTABLE).'.',
        ];
    }

    public function toQuery(): ProductListQuery
    {
        return new ProductListQuery(
            page: (int) ($this->validated('page') ?? 1),
            limit: (int) ($this->validated('limit') ?? ProductListQuery::DEFAULT_LIMIT),
            productName: $this->validatedString('productName'),
            productCode: $this->validatedString('productCode'),
            categoryId: $this->validatedInt('categoryID'),
            subCategoryId: $this->validatedInt('subCategoryID'),
            categoryTypeId: $this->validatedInt('categoryTypeID'),
            bomName: $this->validatedString('bomName'),
            sort: $this->validatedString('sort'),
            flagActive: $this->validatedInt('flagActive'),
        );
    }

    private function validatedString(string $key): ?string
    {
        $value = $this->validated($key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function validatedInt(string $key): ?int
    {
        $value = $this->validated($key);

        return is_numeric($value) ? (int) $value : null;
    }
}
