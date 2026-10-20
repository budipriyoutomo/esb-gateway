<?php

namespace App\Http\Requests\Accounting;

use App\Authorization\Permission;
use App\Domain\Canonical\LedgerSummaryQuery;
use App\Http\Requests\Accounting\Concerns\ValidatesLedgerPeriod;
use App\Http\Requests\BaseFormRequest;

/**
 * The general ledger summary for a period ([ESB-042]). Without a period the
 * page only asks for one, and nothing is sent to the ERP.
 */
class ListLedgerSummaryRequest extends BaseFormRequest
{
    use ValidatesLedgerPeriod;

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
            ...$this->periodRules(),
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.LedgerSummaryQuery::MAX_LIMIT],
            'accountNo' => ['sometimes', 'nullable', 'string', 'regex:/^[0-9A-Za-z._-]{1,32}$/'],
            'branchId' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->periodMessages(),
            'accountNo.regex' => 'Choose an account from the list.',
            'limit.max' => 'The ERP service accepts at most :max records per page.',
        ];
    }

    /**
     * The read to make, or null while the period is not given.
     */
    public function toQuery(): ?LedgerSummaryQuery
    {
        $from = $this->validatedString('dateFrom');
        $to = $this->validatedString('dateTo');

        if ($from === null || $to === null) {
            return null;
        }

        return new LedgerSummaryQuery(
            dateFrom: $from,
            dateTo: $to,
            page: $this->validatedInt('page') ?? 1,
            limit: $this->validatedInt('limit') ?? LedgerSummaryQuery::DEFAULT_LIMIT,
            accountNo: $this->validatedString('accountNo'),
            branchId: $this->validatedInt('branchId'),
        );
    }

    /**
     * The filters echoed back to the page.
     *
     * @return array<string, int|string>
     */
    public function validatedFilters(): array
    {
        return array_filter([
            'page' => $this->validatedInt('page'),
            'dateFrom' => $this->validatedString('dateFrom'),
            'dateTo' => $this->validatedString('dateTo'),
            'accountNo' => $this->validatedString('accountNo'),
            'branchId' => $this->validatedInt('branchId'),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
