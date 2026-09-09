<?php

use App\Authorization\Permission;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\EsbConnections\EsbConnectionController;
use App\Http\Controllers\EsbConnections\EsbConnectionProbeController;
use App\Http\Controllers\IntegrationLogs\IntegrationLogController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Users\UserRoleController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->middleware('can:'.Permission::ViewDashboard->value)
        ->name('dashboard');

    Route::get('products', [ProductController::class, 'index'])->name('products.index');

    Route::get('integration-logs', [IntegrationLogController::class, 'index'])
        ->name('integration-logs.index');

    Route::get('esb-connections', [EsbConnectionController::class, 'index'])
        ->name('esb-connections.index');

    // Probing only. There is deliberately no update route: base URLs and
    // credentials are configuration (ADR-010, ADR-013), so changing one is a
    // deployment action rather than a form submission.
    Route::post('esb-connections/{family}/test', [EsbConnectionProbeController::class, 'store'])
        ->name('esb-connections.test');

    Route::get('users', [UserController::class, 'index'])->name('users.index');

    Route::put('users/{user}/roles', [UserRoleController::class, 'update'])
        ->name('users.roles.update');
});

require __DIR__.'/settings.php';
