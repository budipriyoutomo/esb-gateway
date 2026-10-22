<?php

namespace App\Http\Requests\Menus;

use App\Authorization\Permission;
use App\Domain\Canonical\MasterMenuQuery;
use App\Domain\Canonical\MenuQuery;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the menu list filters before they reach the ESB (ADR-030).
 *
 * The list has a source tab. Core ([ESB-012]) filters by name, code and
 * active flag; OMS Get Menu ([ESB-013]) documents only `page` and
 * `menuCode`, so nothing else is read for it.
 */
class ListMenusRequest extends BaseFormRequest
{
    public const SOURCE_CORE = 'core';

    public const SOURCE_OMS = 'oms';

    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'source' => ['sometimes', 'nullable', 'string', Rule::in([self::SOURCE_CORE, self::SOURCE_OMS])],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];

        if ($this->input('source') === self::SOURCE_OMS) {
            return [
                ...$rules,
                'menuCode' => ['sometimes', 'nullable', 'string', 'max:'.MasterMenuQuery::CODE_MAX_LENGTH],
            ];
        }

        return [
            ...$rules,
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.MenuQuery::MAX_LIMIT],
            'menuName' => ['sometimes', 'nullable', 'string', 'max:'.MenuQuery::NAME_MAX_LENGTH],
            'menuCode' => ['sometimes', 'nullable', 'string', 'max:'.MenuQuery::CODE_MAX_LENGTH],
            'flagActive' => ['sometimes', 'nullable', 'integer', Rule::in([0, 1])],
        ];
    }

    /**
     * @return self::SOURCE_*
     */
    public function source(): string
    {
        return $this->validatedString('source') === self::SOURCE_OMS ? self::SOURCE_OMS : self::SOURCE_CORE;
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

    public function toOmsQuery(): MasterMenuQuery
    {
        return new MasterMenuQuery(
            page: $this->validatedInt('page') ?? 1,
            code: $this->validatedString('menuCode'),
        );
    }

    /**
     * The filters echoed back to the page, in the request's own spelling.
     * `source` is echoed only for the OMS tab: Core is the default.
     *
     * @return array<string, int|string>
     */
    public function validatedFilters(): array
    {
        if ($this->source() === self::SOURCE_OMS) {
            $query = $this->toOmsQuery();

            return array_filter([
                'source' => self::SOURCE_OMS,
                'page' => $query->page,
                'menuCode' => $query->code,
            ], static fn (mixed $value): bool => $value !== null);
        }

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
