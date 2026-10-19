<?php

namespace App\Http\Requests;

use App\Authorization\Permission;
use Illuminate\Validation\Rule;

/**
 * The filters every read-only master data list takes ([ESB-020]–[ESB-023]):
 * page, limit, a search and an active flag. Each list adds its own rules and
 * maps the values onto its query.
 */
abstract class ListReferenceDataRequest extends BaseFormRequest
{
    /**
     * The gateway's own ceiling, matching the other Core list reads: the ESB
     * rejects a limit above 100 elsewhere with EC03100040.
     */
    public const MAX_LIMIT = 100;

    public const DEFAULT_LIMIT = 20;

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
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_LIMIT],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'flagActive' => ['sometimes', 'nullable', 'integer', Rule::in([0, 1])],
            ...$this->extraRules(),
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function extraRules(): array
    {
        return [];
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

    protected function page(): int
    {
        return $this->validatedInt('page') ?? 1;
    }

    protected function limit(): int
    {
        return $this->validatedInt('limit') ?? self::DEFAULT_LIMIT;
    }

    protected function search(): ?string
    {
        $search = $this->validatedString('search');

        return $search === null || trim($search) === '' ? null : trim($search);
    }

    protected function activeOnly(): ?bool
    {
        $flag = $this->validatedInt('flagActive');

        return $flag === null ? null : $flag === 1;
    }

    /**
     * The filters echoed back to the page, in the request's own spelling.
     *
     * @return array<string, int|string>
     */
    public function validatedFilters(): array
    {
        $activeOnly = $this->activeOnly();

        return array_filter([
            'page' => $this->page(),
            'limit' => $this->limit(),
            'search' => $this->search(),
            ...$this->extraFilters(),
            'flagActive' => $activeOnly === null ? null : (int) $activeOnly,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, int|string|null>
     */
    protected function extraFilters(): array
    {
        return [];
    }
}
