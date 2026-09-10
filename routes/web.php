<?php

use App\Authorization\Permission;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Endpoints\EndpointCatalogController;
use App\Http\Controllers\EsbConnections\EsbConnectionController;
use App\Http\Controllers\EsbConnections\EsbConnectionProbeController;
use App\Http\Controllers\Health\HealthController;
use App\Http\Controllers\IntegrationLogs\IntegrationLogController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Users\UserRoleController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

// Unauthenticated: a load balancer cannot log in. The body says what is broken,
// never where or why -- see HealthController.
Route::get('health', HealthController::class)->name('health');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->middleware('can:'.Permission::ViewDashboard->value)
        ->name('dashboard');

    // Routes below reach the ESB, so they share a per-user budget. The
    // vendor's own rate limits are undocumented, and the gateway must not let
    // its users discover them the hard way (config/esb.php).
    Route::middleware('throttle:esb')->group(function () {
        Route::get('products', [ProductController::class, 'index'])->name('products.index');

        Route::post('esb-connections/{family}/test', [EsbConnectionProbeController::class, 'store'])
            ->name('esb-connections.test');
    });

    Route::get('integration-logs', [IntegrationLogController::class, 'index'])
        ->name('integration-logs.index');

    // Read-only: the catalog mirrors docs/esb/, so it has no write actions.
    Route::get('endpoints', [EndpointCatalogController::class, 'index'])
        ->name('endpoints.index');

    // Read-only. There is deliberately no update route: base URLs and
    // credentials are configuration (ADR-010, ADR-013), so changing one is a
    // deployment action rather than a form submission. The probe action is
    // rate limited with the other ESB-reaching routes above.
    Route::get('esb-connections', [EsbConnectionController::class, 'index'])
        ->name('esb-connections.index');

    Route::get('users', [UserController::class, 'index'])->name('users.index');

    Route::put('users/{user}/roles', [UserRoleController::class, 'update'])
        ->name('users.roles.update');
});

require __DIR__.'/settings.php';
