<?php

namespace App\Http\Requests\ProductCategories;

use App\Authorization\Permission;
use App\Domain\Canonical\ProductCategoryDraft;
use App\Http\Requests\BaseFormRequest;

/**
 * A product-category to create, or to replace an existing one with ([ESB-030]).
 *
 * Mirrors the vendor schema of POST /product/category (PUT takes the same):
 * name required, max 50; code max 3; type required; account numbers max 20
 * (held to the COA number shape the gateway accepts elsewhere); notes max
 * 100. Depreciation type ids have no documented legend, so they are plain ids.
 * Field names are the gateway's own; the mapper translates them.
 */
class SaveProductCategoryRequest extends BaseFormRequest
{
    /** The form fields that hold a COA number. */
    public const ACCOUNT_FIELDS = [
        'coaNo', 'cogsCoaNo', 'accrualBasisCoaNo', 'opnameCoaNo',
        'productSalesCoaNo', 'depreciationCoaNo', 'depreciationExpenseCoaNo',
    ];

    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, Permission::ManageProductCategories);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'code' => ['nullable', 'string', 'max:3'],
            'typeId' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:100'],
            ...array_fill_keys(self::ACCOUNT_FIELDS, ['nullable', 'string', 'max:20', 'regex:/^[0-9][0-9A-Za-z._-]*$/']),
            'depreciationMonths' => ['nullable', 'integer', 'min:0', 'max:1200'],
            'fiscalDepreciationMonths' => ['nullable', 'integer', 'min:0', 'max:1200'],
            'depreciationTypeId' => ['nullable', 'integer', 'min:0'],
            'fiscalDepreciationTypeId' => ['nullable', 'integer', 'min:0'],
            'assetTax' => ['sometimes', 'boolean'],
        ];
    }

    public function toDraft(): ProductCategoryDraft
    {
        return new ProductCategoryDraft(
            name: (string) $this->validatedString('name'),
            typeId: (int) $this->validatedInt('typeId'),
            code: $this->validatedString('code'),
            notes: $this->validatedString('notes'),
            coaNo: $this->validatedString('coaNo'),
            cogsCoaNo: $this->validatedString('cogsCoaNo'),
            accrualBasisCoaNo: $this->validatedString('accrualBasisCoaNo'),
            opnameCoaNo: $this->validatedString('opnameCoaNo'),
            productSalesCoaNo: $this->validatedString('productSalesCoaNo'),
            depreciationCoaNo: $this->validatedString('depreciationCoaNo'),
            depreciationExpenseCoaNo: $this->validatedString('depreciationExpenseCoaNo'),
            depreciationMonths: $this->validatedInt('depreciationMonths'),
            fiscalDepreciationMonths: $this->validatedInt('fiscalDepreciationMonths'),
            depreciationTypeId: $this->validatedInt('depreciationTypeId'),
            fiscalDepreciationTypeId: $this->validatedInt('fiscalDepreciationTypeId'),
            assetTax: $this->boolean('assetTax'),
        );
    }
}
