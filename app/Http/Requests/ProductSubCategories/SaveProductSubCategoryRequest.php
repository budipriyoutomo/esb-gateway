<?php

namespace App\Http\Requests\ProductSubCategories;

use App\Authorization\Permission;
use App\Domain\Canonical\ProductSubCategoryDraft;
use App\Http\Requests\BaseFormRequest;

/**
 * A product-sub-category to create, or to replace an existing one with ([ESB-030]).
 *
 * Mirrors the vendor schema of POST /product/sub-category (PUT takes the
 * same): name required, max 50; dead stock threshold required, at least 1
 * day; notes max 100.
 * Field names are the gateway's own; the mapper translates them.
 */
class SaveProductSubCategoryRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, Permission::ManageProductSubCategories);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'deadStockDays' => ['required', 'integer', 'min:1', 'max:36500'],
            'notes' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function toDraft(): ProductSubCategoryDraft
    {
        return new ProductSubCategoryDraft(
            name: (string) $this->validatedString('name'),
            deadStockDays: (int) $this->validatedInt('deadStockDays'),
            notes: $this->validatedString('notes'),
        );
    }
}
