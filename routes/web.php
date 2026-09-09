<?php

use App\Authorization\Permission;
use App\Http\Controllers\Dashboard\DashboardController;
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

    Route::get('users', [UserController::class, 'index'])->name('users.index');

    Route::put('users/{user}/roles', [UserRoleController::class, 'update'])
        ->name('users.roles.update');
});

require __DIR__.'/settings.php';
