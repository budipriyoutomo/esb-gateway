<?php

use App\Http\Controllers\Api\SalesDataController;
use Illuminate\Support\Facades\Route;

/*
| Machine-to-machine routes, under /api (ADR-022). Callers authenticate with
| an API client token from `php artisan api-client:create`, never a session.
*/

Route::prefix('v1')
    ->middleware(['api-client', 'throttle:sales-push-api'])
    ->group(function () {
        // A POS hands over one transaction in the ESB's Push Sales Data V2
        // shape. Answered 202: the ESB is called from the queue.
        Route::post('sales-data', [SalesDataController::class, 'store'])
            ->name('api.sales-data.store');

        Route::get('sales-data/{salesPush}', [SalesDataController::class, 'show'])
            ->name('api.sales-data.show');
    });
