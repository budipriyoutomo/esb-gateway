<?php

namespace App\Http\Requests\MasterMenus;

use App\Authorization\Permission;
use App\Domain\Canonical\MasterMenuQuery;
use App\Http\Requests\BaseFormRequest;

/**
 * Validates the menu list filters before they reach the ESB. Get Menu
 * documents only `page` and `menuCode` ([ESB-013]).
 */
class ListMasterMenusRequest extends BaseFormRequest
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
            'menuCode' => ['sometimes', 'nullable', 'string', 'max:'.MasterMenuQuery::CODE_MAX_LENGTH],
        ];
    }

    public function toQuery(): MasterMenuQuery
    {
        return new MasterMenuQuery(
            page: $this->validatedInt('page') ?? 1,
            code: $this->validatedString('menuCode'),
        );
    }

    /**
     * The filters echoed back to the page, in the request's own spelling.
     *
     * @return array<string, int|string>
     */
    public function validatedFilters(): array
    {
        $query = $this->toQuery();

        return array_filter([
            'page' => $query->page,
            'menuCode' => $query->code,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
