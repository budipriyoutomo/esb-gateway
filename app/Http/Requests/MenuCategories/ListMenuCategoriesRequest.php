<?php

namespace App\Http\Requests\MenuCategories;

use App\Authorization\Permission;
use App\Http\Requests\BaseFormRequest;

/**
 * The menu category list's page ([ESB-047]): the only parameter the vendor
 * documents. Also the "listed on" hint of a category's own pages.
 */
class ListMenuCategoriesRequest extends BaseFormRequest
{
    /** A bound on the page asked for; the vendor documents none. */
    public const MAX_PAGE = 10000;

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
            'page' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_PAGE],
        ];
    }

    public function page(): int
    {
        return $this->validatedInt('page') ?? 1;
    }
}
