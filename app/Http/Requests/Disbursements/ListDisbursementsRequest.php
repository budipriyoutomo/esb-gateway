<?php

namespace App\Http\Requests\Disbursements;

use App\Authorization\Permission;
use App\Domain\Canonical\DisbursementPaymentMethod;
use App\Domain\Canonical\DisbursementQuery;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the disbursement list's filters before they reach the ESB
 * ([ESB-037]). The names are the gateway's own; the adapter translates them.
 * Dates are YYYY-MM-DD.
 */
class ListDisbursementsRequest extends BaseFormRequest
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
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.DisbursementQuery::MAX_LIMIT],
            'number' => ['sometimes', 'nullable', 'string', 'max:64'],
            'dateFrom' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'dateTo' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:dateFrom'],
            'statusId' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'branchId' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'supplierId' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'paymentId' => ['sometimes', 'nullable', 'integer', Rule::enum(DisbursementPaymentMethod::class)],
            'reference' => ['sometimes', 'nullable', 'string', 'max:50'],
            'overdue' => ['sometimes', 'nullable', 'boolean'],
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

    public function toQuery(): DisbursementQuery
    {
        $payment = $this->validatedInt('paymentId');

        return new DisbursementQuery(
            page: $this->validatedInt('page') ?? 1,
            limit: $this->validatedInt('limit') ?? DisbursementQuery::DEFAULT_LIMIT,
            number: $this->validatedString('number'),
            dateFrom: $this->validatedString('dateFrom'),
            dateTo: $this->validatedString('dateTo'),
            statusId: $this->validatedInt('statusId'),
            branchId: $this->validatedInt('branchId'),
            supplierId: $this->validatedInt('supplierId'),
            payment: $payment === null ? null : DisbursementPaymentMethod::from($payment),
            reference: $this->validatedString('reference'),
            overdueOnly: $this->boolean('overdue'),
        );
    }

    /**
     * The filters echoed back to the page.
     *
     * @return array<string, int|string|bool>
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
            'branchId' => $query->branchId,
            'supplierId' => $query->supplierId,
            'paymentId' => $query->payment?->value,
            'reference' => $query->reference,
            'overdue' => $query->overdueOnly ? true : null,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
