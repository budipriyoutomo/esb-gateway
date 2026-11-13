<?php

use App\Http\Controllers\Api\ClosingTotalsController;
use App\Http\Controllers\Api\SalesDataController;
use App\Models\ApiClient;
use Illuminate\Support\Facades\Route;

/*
| Machine-to-machine routes, under /api (ADR-022). Callers authenticate with
| an API client token from `php artisan api-client:create`, never a session,
| and each group names the scope its token must carry (ADR-032).
*/

Route::prefix('v1')
    ->middleware(['api-client:'.ApiClient::SCOPE_SALES_DATA, 'throttle:sales-push-api'])
    ->group(function () {
        // A POS hands over one transaction in the ESB's Push Sales Data V2
        // shape. Answered 202: the ESB is called from the queue.
        Route::post('sales-data', [SalesDataController::class, 'store'])
            ->name('api.sales-data.store');

        Route::get('sales-data/{salesPush}', [SalesDataController::class, 'show'])
            ->name('api.sales-data.show');
    });

Route::prefix('v1')
    ->middleware(['api-client:'.ApiClient::SCOPE_CLOSING_TOTALS, 'throttle:closing-totals-api'])
    ->group(function () {
        // sync-api Maharasa hands over one closing recap per outlet and
        // production date. Held only: an operator sends it to the ESB later.
        Route::post('closing-totals', [ClosingTotalsController::class, 'store'])
            ->name('api.closing-totals.store');
    });
