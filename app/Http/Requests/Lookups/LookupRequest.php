<?php

namespace App\Http\Requests\Lookups;

use App\Authorization\Permission;
use App\Domain\Canonical\LookupKind;
use App\Domain\Canonical\LookupQuery;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a picker query before it reaches the ESB ([ESB-019]).
 */
class LookupRequest extends BaseFormRequest
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
            'search' => ['sometimes', 'nullable', 'string', 'max:'.LookupQuery::MAX_SEARCH],
            'page' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'branchId' => [
                Rule::requiredIf(fn (): bool => $this->kind()->requiresBranch()),
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'branchId.required' => 'Choose a branch first.',
        ];
    }

    public function kind(): LookupKind
    {
        $kind = $this->route('kind');

        return $kind instanceof LookupKind ? $kind : LookupKind::from((string) $kind);
    }

    public function toQuery(): LookupQuery
    {
        return new LookupQuery(
            search: $this->validatedString('search'),
            page: $this->validatedInt('page') ?? 1,
            branchId: $this->validatedInt('branchId'),
        );
    }
}
