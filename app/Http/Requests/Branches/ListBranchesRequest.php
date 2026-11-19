<?php

namespace App\Http\Requests\Branches;

use App\Authorization\Permission;
use App\Domain\Canonical\BranchQuery;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the branch list filters before they reach the ESB ([ESB-051]).
 */
class ListBranchesRequest extends BaseFormRequest
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
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.BranchQuery::MAX_LIMIT],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'flagActive' => ['sometimes', 'nullable', 'integer', Rule::in([0, 1])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'limit.max' => 'At most :max records per page.',
        ];
    }

    public function toQuery(): BranchQuery
    {
        $flagActive = $this->validatedInt('flagActive');

        return new BranchQuery(
            page: $this->validatedInt('page') ?? 1,
            limit: $this->validatedInt('limit') ?? BranchQuery::DEFAULT_LIMIT,
            search: $this->validatedString('search'),
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
            'search' => $query->search,
            'flagActive' => $query->activeOnly === null ? null : (int) $query->activeOnly,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
