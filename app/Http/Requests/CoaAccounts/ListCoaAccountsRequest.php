<?php

namespace App\Http\Requests\CoaAccounts;

use App\Authorization\Permission;
use App\Domain\Canonical\CoaAccountQuery;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the chart of accounts filters ([ESB-025]). They are applied in
 * the gateway; nothing but the bare list is sent upstream.
 */
class ListCoaAccountsRequest extends BaseFormRequest
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
            'page' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'level' => ['sometimes', 'nullable', 'integer', Rule::in(CoaAccountQuery::LEVELS)],
        ];
    }

    public function toQuery(): CoaAccountQuery
    {
        $search = $this->validatedString('search');

        return new CoaAccountQuery(
            page: $this->validatedInt('page') ?? 1,
            search: $search === null || trim($search) === '' ? null : trim($search),
            level: $this->validatedInt('level'),
        );
    }

    /**
     * @return array<string, int|string>
     */
    public function validatedFilters(): array
    {
        $query = $this->toQuery();

        return array_filter([
            'page' => $query->page,
            'search' => $query->search,
            'level' => $query->level,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
