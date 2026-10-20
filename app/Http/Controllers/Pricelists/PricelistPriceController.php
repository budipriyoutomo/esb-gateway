<?php

namespace App\Http\Controllers\Pricelists;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pricelists\LastPricesRequest;
use App\Http\Requests\Pricelists\PurchasePricesRequest;
use App\Integrations\Contracts\PricelistSource;
use Illuminate\Http\JsonResponse;

/**
 * JSON prices for the pricelist form ([ESB-032], S4). Reads; an ESB failure
 * is rendered as JSON by EsbExceptionRenderer.
 */
class PricelistPriceController extends Controller
{
    public function lastPrices(LastPricesRequest $request, PricelistSource $pricelists): JsonResponse
    {
        $prices = $pricelists->lastPrices(
            (int) $request->validated('supplierId'),
            (int) $request->validated('currencyId'),
            (string) $request->validated('priceDate'),
            $request->productDetailIds(),
        );

        // An object keyed by id, even when empty or sequential.
        return response()->json(['prices' => (object) $prices]);
    }

    public function purchasePrices(PurchasePricesRequest $request, PricelistSource $pricelists): JsonResponse
    {
        return response()->json(['invoices' => $pricelists->purchasePrices(
            (int) $request->validated('supplierId'),
            (int) $request->validated('currencyId'),
            (int) $request->validated('productDetailId'),
            $request->boolean('otherSuppliers'),
        )]);
    }
}
