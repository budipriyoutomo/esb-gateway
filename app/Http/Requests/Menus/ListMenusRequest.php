<?php

namespace App\Http\Requests\Menus;

use App\Authorization\Permission;
use App\Domain\Canonical\MenuQuery;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the menu list filters before they reach the ESB ([ESB-012]).
 */
class ListMenusRequest extends BaseFormRequest
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
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.MenuQuery::MAX_LIMIT],
            'menuName' => ['sometimes', 'nullable', 'string', 'max:'.MenuQuery::NAME_MAX_LENGTH],
            'menuCode' => ['sometimes', 'nullable', 'string', 'max:'.MenuQuery::CODE_MAX_LENGTH],
            'flagActive' => ['sometimes', 'nullable', 'integer', Rule::in([0, 1])],
        ];
    }

    public function toQuery(): MenuQuery
    {
        $flagActive = $this->validatedInt('flagActive');

        return new MenuQuery(
            page: $this->validatedInt('page') ?? 1,
            limit: $this->validatedInt('limit') ?? MenuQuery::DEFAULT_LIMIT,
            name: $this->validatedString('menuName'),
            code: $this->validatedString('menuCode'),
            activeOnly: $flagActive === null ? null : $flagActive === 1,
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
            'limit' => $query->limit,
            'menuName' => $query->name,
            'menuCode' => $query->code,
            'flagActive' => $query->activeOnly === null ? null : (int) $query->activeOnly,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
