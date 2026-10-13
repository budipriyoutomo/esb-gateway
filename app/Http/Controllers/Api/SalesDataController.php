<?php

namespace App\Http\Controllers\Api;

use App\Actions\SalesPushes\ReceiveSalesData;
use App\Http\Controllers\Controller;
use App\Http\Middleware\AuthenticateApiClient;
use App\Http\Requests\Api\PushSalesDataRequest;
use App\Http\Resources\SalesPushResource;
use App\Models\SalesPush;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POS-facing sales data API (ADR-022).
 */
class SalesDataController extends Controller
{
    public function store(PushSalesDataRequest $request, ReceiveSalesData $receive): JsonResponse
    {
        $push = $receive->handle(AuthenticateApiClient::client($request), $request->salesData());

        return (new SalesPushResource($push))
            ->response()
            ->setStatusCode(202)
            ->header('Location', route('api.sales-data.show', $push));
    }

    public function show(Request $request, SalesPush $salesPush): SalesPushResource
    {
        // A client sees only what it sent. 404 rather than 403, so ids from
        // another client cannot be probed for existence.
        abort_unless($salesPush->api_client_id === AuthenticateApiClient::client($request)->id, 404);

        return new SalesPushResource($salesPush);
    }
}
