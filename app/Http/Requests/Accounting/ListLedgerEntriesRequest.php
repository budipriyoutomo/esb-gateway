<?php

namespace App\Http\Requests\Accounting;

use App\Authorization\Permission;
use App\Domain\Canonical\LedgerQuery;
use App\Domain\Canonical\LedgerSide;
use App\Http\Requests\Accounting\Concerns\ValidatesLedgerPeriod;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * An account's general ledger over a period ([ESB-042]). The account and
 * both ends of the period come together: with none of them the page only
 * asks for them, and nothing is sent to the ERP.
 */
class ListLedgerEntriesRequest extends BaseFormRequest
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
        $period = $this->periodRules();

        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.LedgerQuery::MAX_LIMIT],
            'coaNo' => ['nullable', 'string', 'regex:/^[0-9A-Za-z._-]{1,32}$/', 'required_with:dateFrom,dateTo'],
            'dateFrom' => [...$period['dateFrom'], 'required_with:coaNo'],
            'dateTo' => [...$period['dateTo'], 'required_with:coaNo'],
            'branchId' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'side' => ['sometimes', 'nullable', 'string', Rule::enum(LedgerSide::class)],
            'reference' => ['sometimes', 'nullable', 'string', 'max:100'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:255'],
            'transactionType' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->periodMessages(),
            'coaNo.required_with' => 'Choose the account.',
            'coaNo.regex' => 'Choose an account from the list.',
            'limit.max' => 'At most :max entries per page.',
        ];
    }

    /**
     * The read to make, or null while the account and period are not given.
     */
    public function toQuery(): ?LedgerQuery
    {
        $coaNo = $this->validatedString('coaNo');
        $from = $this->validatedString('dateFrom');
        $to = $this->validatedString('dateTo');

        if ($coaNo === null || $from === null || $to === null) {
            return null;
        }

        $side = $this->validatedString('side');

        return new LedgerQuery(
            coaNo: $coaNo,
            dateFrom: $from,
            dateTo: $to,
            page: $this->validatedInt('page') ?? 1,
            limit: $this->validatedInt('limit') ?? LedgerQuery::DEFAULT_LIMIT,
            branchId: $this->validatedInt('branchId'),
            side: $side === null ? null : LedgerSide::from($side),
            reference: $this->validatedString('reference'),
            notes: $this->validatedString('notes'),
            transactionType: $this->validatedString('transactionType'),
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
            'coaNo' => $this->validatedString('coaNo'),
            'dateFrom' => $this->validatedString('dateFrom'),
            'dateTo' => $this->validatedString('dateTo'),
            'branchId' => $this->validatedInt('branchId'),
            'side' => $this->validatedString('side'),
            'reference' => $this->validatedString('reference'),
            'notes' => $this->validatedString('notes'),
            'transactionType' => $this->validatedString('transactionType'),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
