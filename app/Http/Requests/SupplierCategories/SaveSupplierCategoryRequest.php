<?php

namespace App\Http\Requests\SupplierCategories;

use App\Authorization\Permission;
use App\Domain\Canonical\SupplierCategoryDraft;
use App\Http\Requests\BaseFormRequest;

/**
 * A supplier-category to create, or to replace an existing one with ([ESB-030]).
 *
 * Mirrors the vendor schema of POST /supplier/category/ (PUT takes the
 * same): name required, ASCII only, max 50.
 * Field names are the gateway's own; the mapper translates them.
 */
class SaveSupplierCategoryRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, Permission::ManageSupplierCategories);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'ascii', 'max:50'],
        ];
    }

    public function toDraft(): SupplierCategoryDraft
    {
        return new SupplierCategoryDraft(name: (string) $this->validatedString('name'));
    }
}
