<?php

namespace App\Http\Requests\Accounting;

use App\Authorization\Permission;
use App\Domain\Canonical\GeneralJournalQuery;
use App\Http\Requests\BaseFormRequest;

/**
 * Validates the general journal's filters before they reach the ESB
 * ([ESB-041]). Dates are YYYY-MM-DD.
 */
class ListGeneralJournalsRequest extends BaseFormRequest
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
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.GeneralJournalQuery::MAX_LIMIT],
            'dateFrom' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'dateTo' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:dateFrom'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'limit.max' => 'The ERP service accepts at most :max records per page.',
            'dateTo.after_or_equal' => 'The end date must be on or after the start date.',
        ];
    }

    public function toQuery(): GeneralJournalQuery
    {
        return new GeneralJournalQuery(
            page: $this->validatedInt('page') ?? 1,
            limit: $this->validatedInt('limit') ?? GeneralJournalQuery::DEFAULT_LIMIT,
            dateFrom: $this->validatedString('dateFrom'),
            dateTo: $this->validatedString('dateTo'),
            search: $this->validatedString('search'),
        );
    }

    /**
     * The filters echoed back to the page.
     *
     * @return array<string, int|string>
     */
    public function validatedFilters(): array
    {
        $query = $this->toQuery();

        return array_filter([
            'page' => $query->page,
            'limit' => $query->limit,
            'dateFrom' => $query->dateFrom,
            'dateTo' => $query->dateTo,
            'search' => $query->search,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
