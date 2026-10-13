<?php

use App\Authorization\Permission;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Endpoints\EndpointCatalogController;
use App\Http\Controllers\EsbConnections\EsbConnectionController;
use App\Http\Controllers\EsbConnections\EsbConnectionProbeController;
use App\Http\Controllers\Health\HealthController;
use App\Http\Controllers\IntegrationLogs\IntegrationLogController;
use App\Http\Controllers\MasterMenus\MasterMenuController;
use App\Http\Controllers\Menus\MenuController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\PurchaseInvoices\PurchaseInvoiceController;
use App\Http\Controllers\PurchaseInvoices\PurchaseInvoiceDecisionController;
use App\Http\Controllers\PurchaseRequests\PurchaseRequestController;
use App\Http\Controllers\PurchaseRequests\PurchaseRequestDecisionController;
use App\Http\Controllers\PurchaseRequests\PurchaseRequestFormController;
use App\Http\Controllers\PurchaseRequests\PurchaseRequestImportController;
use App\Http\Controllers\SalesPushes\SalesPushController;
use App\Http\Controllers\SalesPushes\SalesPushRetryController;
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

        // Read-only master menu list ([ESB-012]). Authorization is on its
        // form request (esb.invoke).
        Route::get('menus', [MenuController::class, 'index'])->name('menus.index');

        // Read-only ESB OMS master menu, with prices, packages, and extras
        // ([ESB-013]). Authorization for the list is on its form request
        // (esb.invoke). The code is sent as a query parameter, but is still
        // held to a plain code here.
        Route::get('master-menus', [MasterMenuController::class, 'index'])->name('master-menus.index');
        Route::get('master-menus/{code}', [MasterMenuController::class, 'show'])
            ->where('code', '[A-Za-z0-9][A-Za-z0-9 _.-]{0,49}')
            ->middleware('can:'.Permission::InvokeEsbOperations->value)
            ->name('master-menus.show');

        Route::get('purchase-requests', [PurchaseRequestController::class, 'index'])
            ->name('purchase-requests.index');

        // Creating, changing, deleting and importing purchasing documents is
        // a business action, so esb.invoke alone does not grant it. These are
        // registered before purchase-requests/{number}, whose pattern would
        // otherwise also match "create" and "import".
        Route::middleware([
            'can:'.Permission::InvokeEsbOperations->value,
            'can:'.Permission::ManagePurchaseRequests->value,
        ])->group(function () {
            Route::get('purchase-requests/create', [PurchaseRequestFormController::class, 'create'])
                ->name('purchase-requests.create');
            Route::post('purchase-requests', [PurchaseRequestFormController::class, 'store'])
                ->name('purchase-requests.store');

            Route::get('purchase-requests/import', [PurchaseRequestImportController::class, 'create'])
                ->name('purchase-requests.import.create');
            Route::post('purchase-requests/import', [PurchaseRequestImportController::class, 'store'])
                ->name('purchase-requests.import.store');

            Route::get('purchase-requests/{number}/edit', [PurchaseRequestFormController::class, 'edit'])
                ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
                ->name('purchase-requests.edit');
            Route::put('purchase-requests/{number}', [PurchaseRequestFormController::class, 'update'])
                ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
                ->name('purchase-requests.update');
            Route::delete('purchase-requests/{number}', [PurchaseRequestFormController::class, 'destroy'])
                ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
                ->name('purchase-requests.destroy');
        });

        // The number is interpolated into the upstream path, so it is held to
        // a plain document number here -- no slashes, dots-only segments, or
        // query characters can reach the ESB.
        Route::get('purchase-requests/{number}', [PurchaseRequestController::class, 'show'])
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware('can:'.Permission::InvokeEsbOperations->value)
            ->name('purchase-requests.show');

        // The first ESB write. Authorization (esb.invoke plus
        // purchase-requests.approve) is on the form request.
        Route::post('purchase-requests/{number}/decisions', [PurchaseRequestDecisionController::class, 'store'])
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->name('purchase-requests.decisions.store');

        // Authorization for the list is on its form request (esb.invoke).
        Route::get('purchase-invoices', [PurchaseInvoiceController::class, 'index'])
            ->name('purchase-invoices.index');

        // Held to a plain document number for the same reason as purchase
        // requests: it is interpolated into the upstream path.
        Route::get('purchase-invoices/{number}', [PurchaseInvoiceController::class, 'show'])
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware('can:'.Permission::InvokeEsbOperations->value)
            ->name('purchase-invoices.show');

        Route::delete('purchase-invoices/{number}', [PurchaseInvoiceController::class, 'destroy'])
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware([
                'can:'.Permission::InvokeEsbOperations->value,
                'can:'.Permission::ManagePurchaseInvoices->value,
            ])
            ->name('purchase-invoices.destroy');

        // Authorization (esb.invoke plus purchase-invoices.approve) is on the
        // form request.
        Route::post('purchase-invoices/{number}/decisions', [PurchaseInvoiceDecisionController::class, 'store'])
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->name('purchase-invoices.decisions.store');

        Route::post('esb-connections/{family}/test', [EsbConnectionProbeController::class, 'store'])
            ->name('esb-connections.test');
    });

    Route::get('integration-logs', [IntegrationLogController::class, 'index'])
        ->name('integration-logs.index');

    // Sales transactions POS clients pushed through /api/v1/sales-data
    // (ADR-022). Read from the gateway's own table. Retrying only queues the
    // push; the ESB is called from the queue, so these are not throttled with
    // the ESB-reaching routes above. Authorization for the list is on its
    // form request.
    Route::get('sales-pushes', [SalesPushController::class, 'index'])
        ->name('sales-pushes.index');
    Route::get('sales-pushes/{salesPush}', [SalesPushController::class, 'show'])
        ->middleware('can:'.Permission::ViewSalesPushes->value)
        ->name('sales-pushes.show');
    Route::post('sales-pushes/{salesPush}/retry', [SalesPushRetryController::class, 'store'])
        ->middleware([
            'can:'.Permission::ViewSalesPushes->value,
            'can:'.Permission::RetrySalesPushes->value,
        ])
        ->name('sales-pushes.retry');

    // Read-only: the catalog mirrors docs/esb/, so it has no write actions.
    Route::get('endpoints', [EndpointCatalogController::class, 'index'])
        ->name('endpoints.index');

    // Editable under esb-connections.manage (ADR-018, superseding ADR-013): an
    // ESB environment has to be repointable without a deploy. Credentials are
    // encrypted at rest and write-only, every change is audited, and the
    // production guard in app:check-production-readiness runs against the
    // effective connection so a row here is not a way around it. The probe
    // action is rate limited with the other ESB-reaching routes above.
    Route::get('esb-connections', [EsbConnectionController::class, 'index'])
        ->name('esb-connections.index');

    Route::put('esb-connections/{family}', [EsbConnectionController::class, 'update'])
        ->name('esb-connections.update');

    // Reverting drops the row and hands the family back to config/esb.php.
    Route::delete('esb-connections/{family}', [EsbConnectionController::class, 'destroy'])
        ->name('esb-connections.destroy');

    Route::get('users', [UserController::class, 'index'])->name('users.index');

    Route::put('users/{user}/roles', [UserRoleController::class, 'update'])
        ->name('users.roles.update');
});

require __DIR__.'/settings.php';
