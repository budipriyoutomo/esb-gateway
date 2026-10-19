<?php

namespace App\Http\Requests\SimplePurchases;

use App\Authorization\Permission;
use App\Domain\Canonical\SimplePurchaseQuery;
use App\Http\Requests\BaseFormRequest;

/**
 * Validates the simple purchase list filters before they reach the ESB.
 *
 * The query-string names are the gateway's own; the adapter translates them.
 * Dates use the YYYY-MM-DD format the vendor documents for dateFrom/dateTo.
 */
class ListSimplePurchasesRequest extends BaseFormRequest
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
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.SimplePurchaseQuery::MAX_LIMIT],
            'number' => ['sometimes', 'nullable', 'string', 'max:64'],
            'supplierInvoiceNumber' => ['sometimes', 'nullable', 'string', 'max:50'],
            'dateFrom' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'dateTo' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:dateFrom'],
            // A single branch: the ESB documents branchID, not branchIDs, here.
            'branchId' => ['sometimes', 'nullable', 'integer', 'min:1'],
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

    public function toQuery(): SimplePurchaseQuery
    {
        return new SimplePurchaseQuery(
            page: $this->validatedInt('page') ?? 1,
            limit: $this->validatedInt('limit') ?? SimplePurchaseQuery::DEFAULT_LIMIT,
            number: $this->validatedString('number'),
            supplierInvoiceNumber: $this->validatedString('supplierInvoiceNumber'),
            dateFrom: $this->validatedString('dateFrom'),
            dateTo: $this->validatedString('dateTo'),
            branchId: $this->validatedInt('branchId'),
            search: $this->validatedString('search'),
        );
    }

    /**
     * The filters echoed back to the page, in the query-string spelling the
     * frontend sends.
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
            'supplierInvoiceNumber' => $query->supplierInvoiceNumber,
            'dateFrom' => $query->dateFrom,
            'dateTo' => $query->dateTo,
            'branchId' => $query->branchId,
            'search' => $query->search,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
