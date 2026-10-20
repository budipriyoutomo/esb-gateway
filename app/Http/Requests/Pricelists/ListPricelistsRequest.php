<?php

namespace App\Http\Requests\Pricelists;

use App\Authorization\Permission;
use App\Domain\Canonical\PricelistQuery;
use App\Domain\Canonical\TempPricelistQuery;
use App\Http\Requests\BaseFormRequest;
use App\Http\Requests\ListReferenceDataRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the pricelist filters ([ESB-027]): page, limit and a search for
 * both lists, plus the documented status for temporary pricelists. The
 * active pricelist has no active flag upstream.
 */
class ListPricelistsRequest extends BaseFormRequest
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
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.ListReferenceDataRequest::MAX_LIMIT],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'statusId' => ['sometimes', 'nullable', 'integer', Rule::in(array_keys(TempPricelistQuery::STATUSES))],
        ];
    }

    public function toQuery(): PricelistQuery
    {
        return new PricelistQuery(page: $this->page(), limit: $this->limit(), search: $this->search());
    }

    public function toTempQuery(): TempPricelistQuery
    {
        return new TempPricelistQuery(
            page: $this->page(),
            limit: $this->limit(),
            search: $this->search(),
            statusId: $this->validatedInt('statusId'),
        );
    }

    /**
     * @return array<string, int|string>
     */
    public function validatedFilters(bool $temp = false): array
    {
        return array_filter([
            'page' => $this->page(),
            'limit' => $this->limit(),
            'search' => $this->search(),
            'statusId' => $temp ? $this->validatedInt('statusId') : null,
        ], static fn (mixed $value): bool => $value !== null);
    }

    private function page(): int
    {
        return $this->validatedInt('page') ?? 1;
    }

    private function limit(): int
    {
        return $this->validatedInt('limit') ?? ListReferenceDataRequest::DEFAULT_LIMIT;
    }

    private function search(): ?string
    {
        $search = $this->validatedString('search');

        return $search === null || trim($search) === '' ? null : trim($search);
    }
}
