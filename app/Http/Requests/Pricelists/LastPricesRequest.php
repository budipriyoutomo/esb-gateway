<?php

namespace App\Http\Requests\Pricelists;

use App\Authorization\Permission;
use App\Http\Requests\BaseFormRequest;

/**
 * Which active prices to look up for a pricelist being filled ([ESB-032]).
 * Every parameter is required by the vendor.
 */
class LastPricesRequest extends BaseFormRequest
{
    /** The most lines a pricelist form holds (SavePricelistRequest). */
    public const MAX_PRODUCTS = SavePricelistRequest::MAX_LINES;

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
            'priceDate' => ['required', 'date_format:Y-m-d'],
            'productDetailIds' => ['required', 'array', 'min:1', 'max:'.self::MAX_PRODUCTS],
            'productDetailIds.*' => ['integer', 'min:1', 'distinct'],
        ];
    }

    /**
     * @return list<int>
     */
    public function productDetailIds(): array
    {
        /** @var list<int|string> $ids */
        $ids = $this->validated('productDetailIds');

        return array_map('intval', $ids);
    }
}
