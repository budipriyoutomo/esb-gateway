<?php

namespace App\Http\Requests\Pricelists;

use App\Authorization\Permission;
use App\Http\Requests\BaseFormRequest;

/**
 * Whose recent purchase invoice prices to look up ([ESB-032]).
 */
class PurchasePricesRequest extends BaseFormRequest
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
            'supplierId' => ['required', 'integer', 'min:1'],
            'currencyId' => ['required', 'integer', 'min:1'],
            'productDetailId' => ['required', 'integer', 'min:1'],
            'otherSuppliers' => ['sometimes', 'boolean'],
        ];
    }
}
