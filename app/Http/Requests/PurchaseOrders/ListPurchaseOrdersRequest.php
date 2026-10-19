<?php

namespace App\Http\Requests\PurchaseOrders;

use App\Authorization\Permission;
use App\Domain\Canonical\PurchaseOrderQuery;
use App\Http\Requests\BaseFormRequest;

/**
 * Validates the purchase order list filters before they reach the ESB.
 *
 * The query-string names are the gateway's own; the adapter translates them.
 * Dates use the YYYY-MM-DD format the vendor documents for both date ranges.
 */
class ListPurchaseOrdersRequest extends BaseFormRequest
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
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.PurchaseOrderQuery::MAX_LIMIT],
            'number' => ['sometimes', 'nullable', 'string', 'max:64'],
            'dateFrom' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'dateTo' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:dateFrom'],
            'requiredDateFrom' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'requiredDateTo' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:requiredDateFrom'],
            // Joined with commas for the ESB, so each entry must be a bare id.
            'branchIds' => ['sometimes', 'array', 'max:100'],
            'branchIds.*' => ['integer', 'min:1'],
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
            'requiredDateTo.after_or_equal' => 'The end date must be on or after the start date.',
        ];
    }

    public function toQuery(): PurchaseOrderQuery
    {
        /** @var list<int|string> $branchIds */
        $branchIds = $this->validated('branchIds') ?? [];

        return new PurchaseOrderQuery(
            page: $this->validatedInt('page') ?? 1,
            limit: $this->validatedInt('limit') ?? PurchaseOrderQuery::DEFAULT_LIMIT,
            number: $this->validatedString('number'),
            dateFrom: $this->validatedString('dateFrom'),
            dateTo: $this->validatedString('dateTo'),
            requiredDateFrom: $this->validatedString('requiredDateFrom'),
            requiredDateTo: $this->validatedString('requiredDateTo'),
            branchIds: array_values(array_unique(array_map('intval', $branchIds))),
            search: $this->validatedString('search'),
        );
    }

    /**
     * The filters echoed back to the page, in the query-string spelling the
     * frontend sends.
     *
     * @return array<string, int|string|list<int>>
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
            'requiredDateFrom' => $query->requiredDateFrom,
            'requiredDateTo' => $query->requiredDateTo,
            'branchIds' => $query->branchIds === [] ? null : $query->branchIds,
            'search' => $query->search,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
