<?php

namespace App\Http\Requests\BillsOfMaterial;

use App\Authorization\Permission;
use App\Domain\Canonical\BillOfMaterialQuery;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the BOM list filters before they reach the ESB ([ESB-014]).
 */
class ListBillsOfMaterialRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.BillOfMaterialQuery::MAX_LIMIT],
            'bomName' => ['sometimes', 'nullable', 'string', 'max:255'],
            'bomCode' => ['sometimes', 'nullable', 'string', 'max:255'],
            'productName' => ['sometimes', 'nullable', 'string', 'max:255'],
            'flagActive' => ['sometimes', 'nullable', 'integer', Rule::in([0, 1])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'limit.max' => 'The ERP service accepts at most :max records per page.',
        ];
    }

    public function toQuery(): BillOfMaterialQuery
    {
        $flagActive = $this->validatedInt('flagActive');

        return new BillOfMaterialQuery(
            page: $this->validatedInt('page') ?? 1,
            limit: $this->validatedInt('limit') ?? BillOfMaterialQuery::DEFAULT_LIMIT,
            name: $this->validatedString('bomName'),
            code: $this->validatedString('bomCode'),
            productName: $this->validatedString('productName'),
            activeOnly: $flagActive === null ? null : $flagActive === 1,
        );
    }

    /**
     * The filters echoed back to the page, in the request's own spelling.
     *
     * @return array<string, int|string>
     */
    public function validatedFilters(): array
    {
        $query = $this->toQuery();

        return array_filter([
            'page' => $query->page,
            'limit' => $query->limit,
            'bomName' => $query->name,
            'bomCode' => $query->code,
            'productName' => $query->productName,
            'flagActive' => $query->activeOnly === null ? null : (int) $query->activeOnly,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
