<?php

namespace App\Http\Requests\Accounting;

use App\Authorization\Permission;
use App\Domain\Canonical\MemorialJournalQuery;
use App\Http\Requests\BaseFormRequest;

/**
 * Validates the memorial journal list's filters before they reach the ESB
 * ([ESB-043]). Dates are YYYY-MM-DD.
 */
class ListMemorialJournalsRequest extends BaseFormRequest
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
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.MemorialJournalQuery::MAX_LIMIT],
            'number' => ['sometimes', 'nullable', 'string', 'max:64'],
            'dateFrom' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'dateTo' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:dateFrom'],
            'statusId' => ['sometimes', 'nullable', 'integer', 'min:1'],
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

    public function toQuery(): MemorialJournalQuery
    {
        return new MemorialJournalQuery(
            page: $this->validatedInt('page') ?? 1,
            limit: $this->validatedInt('limit') ?? MemorialJournalQuery::DEFAULT_LIMIT,
            number: $this->validatedString('number'),
            dateFrom: $this->validatedString('dateFrom'),
            dateTo: $this->validatedString('dateTo'),
            statusId: $this->validatedInt('statusId'),
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
            'number' => $query->number,
            'dateFrom' => $query->dateFrom,
            'dateTo' => $query->dateTo,
            'statusId' => $query->statusId,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
