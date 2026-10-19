<?php

namespace App\Http\Requests\Purposes;

use App\Authorization\Permission;
use App\Domain\Canonical\PurposeQuery;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the purpose list filters before they reach the ESB ([ESB-015]).
 */
class ListPurposesRequest extends BaseFormRequest
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
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.PurposeQuery::MAX_LIMIT],
            'purposeName' => ['sometimes', 'nullable', 'string', 'max:255'],
            'purposeCoaNo' => ['sometimes', 'nullable', 'string', 'max:255'],
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

    public function toQuery(): PurposeQuery
    {
        $flagActive = $this->validatedInt('flagActive');

        return new PurposeQuery(
            page: $this->validatedInt('page') ?? 1,
            limit: $this->validatedInt('limit') ?? PurposeQuery::DEFAULT_LIMIT,
            name: $this->validatedString('purposeName'),
            accountNo: $this->validatedString('purposeCoaNo'),
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
            'purposeName' => $query->name,
            'purposeCoaNo' => $query->accountNo,
            'flagActive' => $query->activeOnly === null ? null : (int) $query->activeOnly,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
