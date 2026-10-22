<?php

use App\Authorization\InventoryPermissions;
use App\Authorization\Permission;
use App\Domain\Canonical\InventoryKind;
use App\Http\Controllers\Accounting\GeneralJournalController;
use App\Http\Controllers\Accounting\GeneralLedgerController;
use App\Http\Controllers\Accounting\MemorialJournalAttachmentController;
use App\Http\Controllers\Accounting\MemorialJournalController;
use App\Http\Controllers\Accounting\MemorialJournalFormController;
use App\Http\Controllers\Accounting\MemorialJournalWriteController;
use App\Http\Controllers\BillsOfMaterial\BillOfMaterialController;
use App\Http\Controllers\CoaAccounts\CoaAccountController;
use App\Http\Controllers\CoaAccounts\CoaAccountWriteController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Disbursements\DisbursementAttachmentController;
use App\Http\Controllers\Disbursements\DisbursementController;
use App\Http\Controllers\Disbursements\DisbursementFormController;
use App\Http\Controllers\Disbursements\DisbursementFormLinesController;
use App\Http\Controllers\Disbursements\DisbursementWriteController;
use App\Http\Controllers\DocumentTemplates\DocumentTemplateController;
use App\Http\Controllers\DocumentTemplates\DocumentTemplateFormController;
use App\Http\Controllers\DocumentTemplates\DocumentTemplateImportController;
use App\Http\Controllers\Endpoints\EndpointCatalogController;
use App\Http\Controllers\EsbConnections\EsbConnectionController;
use App\Http\Controllers\EsbConnections\EsbConnectionProbeController;
use App\Http\Controllers\Exports\ListExportController;
use App\Http\Controllers\Health\HealthController;
use App\Http\Controllers\IntegrationLogs\IntegrationLogController;
use App\Http\Controllers\Inventory\InventoryAttachmentController;
use App\Http\Controllers\Inventory\InventoryDocumentController;
use App\Http\Controllers\Inventory\InventoryDocumentFormController;
use App\Http\Controllers\Inventory\InventoryDocumentWriteController;
use App\Http\Controllers\Lookups\LookupController;
use App\Http\Controllers\MasterData\MasterDataImportController;
use App\Http\Controllers\MasterMenus\MasterMenuController;
use App\Http\Controllers\MenuCategories\MenuCategoryController;
use App\Http\Controllers\MenuCategories\MenuCategoryFormController;
use App\Http\Controllers\Menus\MenuController;
use App\Http\Controllers\Pricelists\PricelistController;
use App\Http\Controllers\Pricelists\PricelistFormController;
use App\Http\Controllers\Pricelists\PricelistPriceController;
use App\Http\Controllers\Pricelists\TempPricelistWriteController;
use App\Http\Controllers\ProductCategories\ProductCategoryController;
use App\Http\Controllers\ProductCategories\ProductCategoryFormController;
use App\Http\Controllers\ProductCategories\ProductCategoryWriteController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\Products\ProductFormController;
use App\Http\Controllers\Products\ProductWriteController;
use App\Http\Controllers\ProductSubCategories\ProductSubCategoryController;
use App\Http\Controllers\ProductSubCategories\ProductSubCategoryFormController;
use App\Http\Controllers\ProductSubCategories\ProductSubCategoryWriteController;
use App\Http\Controllers\Promotions\PromotionController;
use App\Http\Controllers\Promotions\PromotionFormController;
use App\Http\Controllers\PurchaseInvoices\PurchaseInvoiceController;
use App\Http\Controllers\PurchaseInvoices\PurchaseInvoiceDecisionController;
use App\Http\Controllers\PurchaseOrders\PurchaseOrderController;
use App\Http\Controllers\PurchaseOrders\PurchaseOrderDecisionController;
use App\Http\Controllers\PurchaseRequests\PurchaseRequestController;
use App\Http\Controllers\PurchaseRequests\PurchaseRequestDecisionController;
use App\Http\Controllers\PurchaseRequests\PurchaseRequestFormController;
use App\Http\Controllers\PurchaseRequests\PurchaseRequestImportController;
use App\Http\Controllers\Purposes\PurposeController;
use App\Http\Controllers\SalesPushes\SalesPushController;
use App\Http\Controllers\SalesPushes\SalesPushRetryController;
use App\Http\Controllers\SimplePurchases\SimplePurchaseController;
use App\Http\Controllers\SimplePurchases\SimplePurchaseDecisionController;
use App\Http\Controllers\SupplierCategories\SupplierCategoryController;
use App\Http\Controllers\SupplierCategories\SupplierCategoryFormController;
use App\Http\Controllers\SupplierCategories\SupplierCategoryWriteController;
use App\Http\Controllers\Suppliers\SupplierAttachmentController;
use App\Http\Controllers\Suppliers\SupplierController;
use App\Http\Controllers\Suppliers\SupplierWriteController;
use App\Http\Controllers\Units\UnitFormController;
use App\Http\Controllers\Units\UnitOfMeasureController;
use App\Http\Controllers\Units\UnitWriteController;
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
        // JSON for searchable pickers ([ESB-019]). Authorization (esb.invoke)
        // is on the form request; an unknown kind is a 404 by enum binding.
        Route::get('lookups/{kind}', [LookupController::class, 'show'])->name('lookups.show');

        // List exports: CSV built by the gateway (ADR-031). Each takes its
        // list's filters, validated and authorized by the list's form request
        // (esb.invoke). Registered before the detail routes, whose number
        // patterns would otherwise take "export".
        Route::get('units/export', [ListExportController::class, 'units'])->name('units.export');
        Route::get('product-categories/export', [ListExportController::class, 'productCategories'])->name('product-categories.export');
        Route::get('product-sub-categories/export', [ListExportController::class, 'productSubCategories'])->name('product-sub-categories.export');
        Route::get('products/export', [ListExportController::class, 'products'])->name('products.export');
        Route::get('pricelists/export', [ListExportController::class, 'pricelists'])->name('pricelists.export');
        Route::get('coa-accounts/export', [ListExportController::class, 'coaAccounts'])->name('coa-accounts.export');
        Route::get('suppliers/export', [ListExportController::class, 'suppliers'])->name('suppliers.export');
        Route::get('disbursements/export', [ListExportController::class, 'disbursements'])->name('disbursements.export');
        Route::get('general-journals/export', [ListExportController::class, 'generalJournals'])->name('general-journals.export');
        Route::get('memorial-journals/export', [ListExportController::class, 'memorialJournals'])->name('memorial-journals.export');
        Route::get('general-ledger/export', [ListExportController::class, 'ledgerEntries'])->name('general-ledger.export');
        Route::get('general-ledger/summary/export', [ListExportController::class, 'ledgerSummary'])->name('general-ledger.summary.export');

        Route::get('products', [ProductController::class, 'index'])->name('products.index');
        // [ESB-026]. The id is interpolated upstream next to literal paths
        // (/product/list), so it is held to a positive integer.
        Route::get('products/{productId}', [ProductController::class, 'show'])
            ->where('productId', '[1-9][0-9]{0,9}')
            ->middleware('can:'.Permission::InvokeEsbOperations->value)
            ->name('products.show');
        // Creating and editing products ([ESB-031]).
        Route::get('products/create', [ProductFormController::class, 'create'])
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageProducts->value])
            ->name('products.create');
        Route::post('products', [ProductFormController::class, 'store'])
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageProducts->value])
            ->name('products.store');
        Route::get('products/{productId}/edit', [ProductFormController::class, 'edit'])
            ->where('productId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageProducts->value])
            ->name('products.edit');
        Route::put('products/{productId}', [ProductFormController::class, 'update'])
            ->where('productId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageProducts->value])
            ->name('products.update');
        Route::delete('products/{productId}', [ProductWriteController::class, 'destroy'])
            ->where('productId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageProducts->value])
            ->name('products.destroy');
        Route::patch('products/{productId}/restore', [ProductWriteController::class, 'restore'])
            ->where('productId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageProducts->value])
            ->name('products.restore');

        // The one menu list, source tab core (ESB Core, [ESB-012]) or oms
        // (ESB OMS, [ESB-013]) -- ADR-030. Authorization is on its form
        // request (esb.invoke).
        Route::get('menus', [MenuController::class, 'index'])->name('menus.index');

        // Read-only ESB OMS master menu detail and extras ([ESB-013]). The old
        // list URL redirects to the oms tab of /menus. The code is sent as a
        // query parameter, but is still held to a plain code here.
        Route::get('master-menus', [MasterMenuController::class, 'index'])->name('master-menus.index');
        // Extras across the menus of one page (TODO-modules §6.3). Registered
        // before {code}, which would otherwise take "extras" as a menu code.
        Route::get('master-menus/extras', [MasterMenuController::class, 'extras'])->name('master-menus.extras');
        Route::get('master-menus/{code}', [MasterMenuController::class, 'show'])
            ->where('code', '[A-Za-z0-9][A-Za-z0-9 _.-]{0,49}')
            ->middleware('can:'.Permission::InvokeEsbOperations->value)
            ->name('master-menus.show');

        // POS menu categories, ESB OMS ([ESB-047], [ESB-048]). Reading needs
        // esb.invoke (on the form requests); create / edit need
        // menu-categories.manage. The id is a positive integer; "create"
        // comes before the id routes.
        $manageMenuCategories = ['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageMenuCategories->value];
        Route::get('menu-categories/create', [MenuCategoryFormController::class, 'create'])
            ->middleware($manageMenuCategories)
            ->name('menu-categories.create');
        Route::post('menu-categories', [MenuCategoryFormController::class, 'store'])
            ->name('menu-categories.store');
        Route::get('menu-categories', [MenuCategoryController::class, 'index'])
            ->name('menu-categories.index');
        Route::get('menu-categories/{id}', [MenuCategoryController::class, 'show'])
            ->where('id', '[1-9][0-9]{0,9}')
            ->name('menu-categories.show');
        Route::get('menu-categories/{id}/edit', [MenuCategoryFormController::class, 'edit'])
            ->where('id', '[1-9][0-9]{0,9}')
            ->middleware($manageMenuCategories)
            ->name('menu-categories.edit');
        Route::put('menu-categories/{id}', [MenuCategoryFormController::class, 'update'])
            ->where('id', '[1-9][0-9]{0,9}')
            ->name('menu-categories.update');

        // POS promotions, ESB OMS ([ESB-049], [ESB-050]). Reading needs
        // esb.invoke (on the form request); create needs promotions.manage.
        Route::get('promotions', [PromotionController::class, 'index'])
            ->name('promotions.index');
        Route::get('promotions/create', [PromotionFormController::class, 'create'])
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManagePromotions->value])
            ->name('promotions.create');
        Route::post('promotions', [PromotionFormController::class, 'store'])
            ->name('promotions.store');

        // Read-only ESB Core bills of material ([ESB-014]). Authorization for
        // the list is on its form request (esb.invoke). The id is interpolated
        // into the upstream path, so it is held to a positive integer here.
        Route::get('bills-of-material', [BillOfMaterialController::class, 'index'])
            ->name('bills-of-material.index');
        Route::get('bills-of-material/{bomId}', [BillOfMaterialController::class, 'show'])
            ->where('bomId', '[1-9][0-9]{0,9}')
            ->middleware('can:'.Permission::InvokeEsbOperations->value)
            ->name('bills-of-material.show');

        // Read-only ESB Core purposes ([ESB-015]). Authorization for the list
        // is on its form request (esb.invoke). The id is interpolated into the
        // upstream path, so it is held to a positive integer here.
        Route::get('purposes', [PurposeController::class, 'index'])
            ->name('purposes.index');
        Route::get('purposes/{purposeId}', [PurposeController::class, 'show'])
            ->where('purposeId', '[1-9][0-9]{0,9}')
            ->middleware('can:'.Permission::InvokeEsbOperations->value)
            ->name('purposes.show');

        // Read-only master data ([ESB-020]–[ESB-023]). Authorization for each
        // list is on its form request (esb.invoke). Ids are interpolated into
        // upstream paths, so they are held to positive integers here.
        Route::get('units', [UnitOfMeasureController::class, 'index'])
            ->name('units.index');
        Route::get('units/{uomId}', [UnitOfMeasureController::class, 'show'])
            ->where('uomId', '[1-9][0-9]{0,9}')
            ->middleware('can:'.Permission::InvokeEsbOperations->value)
            ->name('units.show');

        Route::get('product-categories', [ProductCategoryController::class, 'index'])
            ->name('product-categories.index');
        Route::get('product-categories/{categoryId}', [ProductCategoryController::class, 'show'])
            ->where('categoryId', '[1-9][0-9]{0,9}')
            ->middleware('can:'.Permission::InvokeEsbOperations->value)
            ->name('product-categories.show');

        Route::get('product-sub-categories', [ProductSubCategoryController::class, 'index'])
            ->name('product-sub-categories.index');
        Route::get('product-sub-categories/{subCategoryId}', [ProductSubCategoryController::class, 'show'])
            ->where('subCategoryId', '[1-9][0-9]{0,9}')
            ->middleware('can:'.Permission::InvokeEsbOperations->value)
            ->name('product-sub-categories.show');

        Route::get('supplier-categories', [SupplierCategoryController::class, 'index'])
            ->name('supplier-categories.index');
        Route::get('supplier-categories/{supplierCategoryId}', [SupplierCategoryController::class, 'show'])
            ->where('supplierCategoryId', '[1-9][0-9]{0,9}')
            ->middleware('can:'.Permission::InvokeEsbOperations->value)
            ->name('supplier-categories.show');

        Route::get('suppliers', [SupplierController::class, 'index'])
            ->name('suppliers.index');
        Route::get('suppliers/{supplierId}', [SupplierController::class, 'show'])
            ->where('supplierId', '[1-9][0-9]{0,9}')
            ->middleware('can:'.Permission::InvokeEsbOperations->value)
            ->name('suppliers.show');
        Route::patch('suppliers/{supplierId}/deactivate', [SupplierWriteController::class, 'deactivate'])
            ->where('supplierId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageSuppliers->value])
            ->name('suppliers.deactivate');
        Route::patch('suppliers/{supplierId}/restore', [SupplierWriteController::class, 'restore'])
            ->where('supplierId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageSuppliers->value])
            ->name('suppliers.restore');
        // Supplier attachments ([ESB-032]). POST, not PATCH: PHP parses
        // multipart bodies only on POST.
        Route::post('suppliers/{supplierId}/attachments', [SupplierAttachmentController::class, 'store'])
            ->where('supplierId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageSuppliers->value])
            ->name('suppliers.attachments.store');
        Route::delete('suppliers/{supplierId}/attachments', [SupplierAttachmentController::class, 'destroy'])
            ->where('supplierId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageSuppliers->value])
            ->name('suppliers.attachments.destroy');

        // Master data create / update (TODO-modules.md §2, S3, [ESB-030]):
        // esb.invoke and the module's own manage permission. "create" never
        // matches the numeric id patterns, so the order does not matter.
        Route::get('units/create', [UnitFormController::class, 'create'])
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageUnits->value])
            ->name('units.create');
        Route::post('units', [UnitFormController::class, 'store'])
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageUnits->value])
            ->name('units.store');
        Route::get('units/{uomId}/edit', [UnitFormController::class, 'edit'])
            ->where('uomId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageUnits->value])
            ->name('units.edit');
        Route::put('units/{uomId}', [UnitFormController::class, 'update'])
            ->where('uomId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageUnits->value])
            ->name('units.update');
        Route::get('product-sub-categories/create', [ProductSubCategoryFormController::class, 'create'])
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageProductSubCategories->value])
            ->name('product-sub-categories.create');
        Route::post('product-sub-categories', [ProductSubCategoryFormController::class, 'store'])
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageProductSubCategories->value])
            ->name('product-sub-categories.store');
        Route::get('product-sub-categories/{subCategoryId}/edit', [ProductSubCategoryFormController::class, 'edit'])
            ->where('subCategoryId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageProductSubCategories->value])
            ->name('product-sub-categories.edit');
        Route::put('product-sub-categories/{subCategoryId}', [ProductSubCategoryFormController::class, 'update'])
            ->where('subCategoryId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageProductSubCategories->value])
            ->name('product-sub-categories.update');
        Route::get('supplier-categories/create', [SupplierCategoryFormController::class, 'create'])
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageSupplierCategories->value])
            ->name('supplier-categories.create');
        Route::post('supplier-categories', [SupplierCategoryFormController::class, 'store'])
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageSupplierCategories->value])
            ->name('supplier-categories.store');
        Route::get('supplier-categories/{supplierCategoryId}/edit', [SupplierCategoryFormController::class, 'edit'])
            ->where('supplierCategoryId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageSupplierCategories->value])
            ->name('supplier-categories.edit');
        Route::put('supplier-categories/{supplierCategoryId}', [SupplierCategoryFormController::class, 'update'])
            ->where('supplierCategoryId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageSupplierCategories->value])
            ->name('supplier-categories.update');
        Route::get('product-categories/create', [ProductCategoryFormController::class, 'create'])
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageProductCategories->value])
            ->name('product-categories.create');
        Route::post('product-categories', [ProductCategoryFormController::class, 'store'])
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageProductCategories->value])
            ->name('product-categories.store');
        Route::get('product-categories/{categoryId}/edit', [ProductCategoryFormController::class, 'edit'])
            ->where('categoryId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageProductCategories->value])
            ->name('product-categories.edit');
        Route::put('product-categories/{categoryId}', [ProductCategoryFormController::class, 'update'])
            ->where('categoryId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageProductCategories->value])
            ->name('product-categories.update');

        // Inventory create / edit ([ESB-035], S3) for the kinds the gateway
        // edits: esb.invoke + <kind>.manage. Registered before the details,
        // whose number pattern would also match "create".
        foreach (array_filter(InventoryKind::cases(), static fn (InventoryKind $kind): bool => $kind->editable()) as $inventoryKind) {
            $manage = ['can:'.Permission::InvokeEsbOperations->value, 'can:'.InventoryPermissions::manage($inventoryKind)->value];

            Route::get($inventoryKind->value.'/create', [InventoryDocumentFormController::class, 'create'])
                ->defaults('kind', $inventoryKind->value)
                ->middleware($manage)
                ->name($inventoryKind->value.'.create');
            Route::post($inventoryKind->value, [InventoryDocumentFormController::class, 'store'])
                ->defaults('kind', $inventoryKind->value)
                ->name($inventoryKind->value.'.store');
            Route::get($inventoryKind->value.'/{number}/edit', [InventoryDocumentFormController::class, 'edit'])
                ->defaults('kind', $inventoryKind->value)
                ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
                ->middleware($manage)
                ->name($inventoryKind->value.'.edit');
            Route::put($inventoryKind->value.'/{number}', [InventoryDocumentFormController::class, 'update'])
                ->defaults('kind', $inventoryKind->value)
                ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
                ->name($inventoryKind->value.'.update');
        }

        // Inventory documents ([ESB-033], ADR-026): list and detail per kind.
        // Authorization (esb.invoke) is on the form request for the lists and
        // on the route for the details; the controller requires a digit in the
        // number (literal vendor paths share the segment).
        Route::get('goods-transfer-requests', [InventoryDocumentController::class, 'index'])
            ->defaults('kind', 'goods-transfer-requests')
            ->name('goods-transfer-requests.index');
        Route::get('goods-transfer-requests/{number}', [InventoryDocumentController::class, 'show'])
            ->defaults('kind', 'goods-transfer-requests')
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware('can:'.Permission::InvokeEsbOperations->value)
            ->name('goods-transfer-requests.show');
        Route::get('goods-deliveries', [InventoryDocumentController::class, 'index'])
            ->defaults('kind', 'goods-deliveries')
            ->name('goods-deliveries.index');
        Route::get('goods-deliveries/{number}', [InventoryDocumentController::class, 'show'])
            ->defaults('kind', 'goods-deliveries')
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware('can:'.Permission::InvokeEsbOperations->value)
            ->name('goods-deliveries.show');
        Route::get('goods-receipts', [InventoryDocumentController::class, 'index'])
            ->defaults('kind', 'goods-receipts')
            ->name('goods-receipts.index');
        Route::get('goods-receipts/{number}', [InventoryDocumentController::class, 'show'])
            ->defaults('kind', 'goods-receipts')
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware('can:'.Permission::InvokeEsbOperations->value)
            ->name('goods-receipts.show');
        Route::get('item-journals', [InventoryDocumentController::class, 'index'])
            ->defaults('kind', 'item-journals')
            ->name('item-journals.index');
        Route::get('item-journals/{number}', [InventoryDocumentController::class, 'show'])
            ->defaults('kind', 'item-journals')
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware('can:'.Permission::InvokeEsbOperations->value)
            ->name('item-journals.show');
        Route::get('simple-transfers', [InventoryDocumentController::class, 'index'])
            ->defaults('kind', 'simple-transfers')
            ->name('simple-transfers.index');
        Route::get('simple-transfers/{number}', [InventoryDocumentController::class, 'show'])
            ->defaults('kind', 'simple-transfers')
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware('can:'.Permission::InvokeEsbOperations->value)
            ->name('simple-transfers.show');

        // Inventory writes ([ESB-034], S2), per kind: decisions need
        // <kind>.approve (checked by the form request), delete needs
        // <kind>.manage; both need esb.invoke. The controller requires a
        // digit in the number, as for the detail.
        foreach (InventoryKind::cases() as $inventoryKind) {
            $manage = ['can:'.Permission::InvokeEsbOperations->value, 'can:'.InventoryPermissions::manage($inventoryKind)->value];

            Route::post($inventoryKind->value.'/{number}/decisions', [InventoryDocumentWriteController::class, 'decide'])
                ->defaults('kind', $inventoryKind->value)
                ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
                ->name($inventoryKind->value.'.decisions.store');
            Route::delete($inventoryKind->value.'/{number}', [InventoryDocumentWriteController::class, 'destroy'])
                ->defaults('kind', $inventoryKind->value)
                ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
                ->middleware($manage)
                ->name($inventoryKind->value.'.destroy');
        }
        // Inventory attachments ([ESB-036], S4): esb.invoke + <kind>.manage.
        // POST, not PATCH: PHP parses multipart bodies only on POST.
        foreach (array_filter(InventoryKind::cases(), static fn (InventoryKind $kind): bool => $kind->takesAttachments()) as $inventoryKind) {
            $manage = ['can:'.Permission::InvokeEsbOperations->value, 'can:'.InventoryPermissions::manage($inventoryKind)->value];

            Route::post($inventoryKind->value.'/{number}/attachments', [InventoryAttachmentController::class, 'store'])
                ->defaults('kind', $inventoryKind->value)
                ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
                ->middleware($manage)
                ->name($inventoryKind->value.'.attachments.store');

            if ($inventoryKind->attachmentsRemovable()) {
                Route::delete($inventoryKind->value.'/{number}/attachments', [InventoryAttachmentController::class, 'destroy'])
                    ->defaults('kind', $inventoryKind->value)
                    ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
                    ->middleware($manage)
                    ->name($inventoryKind->value.'.attachments.destroy');
            }
        }
        Route::patch('goods-transfer-requests/{number}/finish', [InventoryDocumentWriteController::class, 'finish'])
            ->defaults('kind', InventoryKind::GoodsTransferRequests->value)
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageGoodsTransferRequests->value])
            ->name('goods-transfer-requests.finish');
        Route::patch('goods-transfer-requests/{number}/reopen', [InventoryDocumentWriteController::class, 'reopen'])
            ->defaults('kind', InventoryKind::GoodsTransferRequests->value)
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageGoodsTransferRequests->value])
            ->name('goods-transfer-requests.reopen');
        // Disbursements ([ESB-037]–[ESB-040]). Reading needs esb.invoke (on
        // the form request for the list, the route for the detail); decisions
        // need disbursements.approve (checked by the form request); everything
        // else disbursements.manage. The controller requires a digit in the
        // number (/finance/disbursement/import shares the segment). The form
        // routes come first: "create" would match the number pattern.
        $manageDisbursements = ['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageDisbursements->value];
        Route::get('disbursements/create', [DisbursementFormController::class, 'create'])
            ->middleware($manageDisbursements)
            ->name('disbursements.create');
        Route::post('disbursements', [DisbursementFormController::class, 'store'])
            ->name('disbursements.store');
        Route::post('disbursements/form-lines', [DisbursementFormLinesController::class, 'store'])
            ->name('disbursements.form-lines');
        Route::get('disbursements', [DisbursementController::class, 'index'])
            ->name('disbursements.index');
        Route::get('disbursements/{number}', [DisbursementController::class, 'show'])
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware('can:'.Permission::InvokeEsbOperations->value)
            ->name('disbursements.show');
        Route::get('disbursements/{number}/edit', [DisbursementFormController::class, 'edit'])
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware($manageDisbursements)
            ->name('disbursements.edit');
        Route::put('disbursements/{number}', [DisbursementFormController::class, 'update'])
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->name('disbursements.update');
        Route::post('disbursements/{number}/decisions', [DisbursementWriteController::class, 'decide'])
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->name('disbursements.decisions.store');
        Route::delete('disbursements/{number}', [DisbursementWriteController::class, 'destroy'])
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware($manageDisbursements)
            ->name('disbursements.destroy');
        // POST, not PATCH: PHP parses multipart bodies only on POST.
        Route::post('disbursements/{number}/attachments', [DisbursementAttachmentController::class, 'store'])
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware($manageDisbursements)
            ->name('disbursements.attachments.store');

        // Accounting reads ([ESB-041]–[ESB-043], S1). Authorization
        // (esb.invoke) is on the form requests, and on the route for the
        // memorial journal detail. A journal head id is a positive integer; a
        // memorial journal number must contain a digit (checked by the
        // controller: literal vendor paths share the segment).
        Route::get('general-journals', [GeneralJournalController::class, 'index'])
            ->name('general-journals.index');
        Route::get('general-journals/{journalHeadId}', [GeneralJournalController::class, 'show'])
            ->where('journalHeadId', '[1-9][0-9]{0,9}')
            ->name('general-journals.show');
        Route::get('general-ledger', [GeneralLedgerController::class, 'index'])
            ->name('general-ledger.index');
        Route::get('general-ledger/summary', [GeneralLedgerController::class, 'summary'])
            ->name('general-ledger.summary');
        // Memorial journal writes ([ESB-044]–[ESB-046]): decisions need
        // memorial-journals.approve (checked by the form request); everything
        // else memorial-journals.manage. The form routes come first: "create"
        // would match the number pattern.
        $manageMemorialJournals = ['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageMemorialJournals->value];
        Route::get('memorial-journals/create', [MemorialJournalFormController::class, 'create'])
            ->middleware($manageMemorialJournals)
            ->name('memorial-journals.create');
        Route::post('memorial-journals', [MemorialJournalFormController::class, 'store'])
            ->name('memorial-journals.store');
        Route::get('memorial-journals', [MemorialJournalController::class, 'index'])
            ->name('memorial-journals.index');
        Route::get('memorial-journals/{number}', [MemorialJournalController::class, 'show'])
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware('can:'.Permission::InvokeEsbOperations->value)
            ->name('memorial-journals.show');
        Route::get('memorial-journals/{number}/edit', [MemorialJournalFormController::class, 'edit'])
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware($manageMemorialJournals)
            ->name('memorial-journals.edit');
        Route::put('memorial-journals/{number}', [MemorialJournalFormController::class, 'update'])
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->name('memorial-journals.update');
        Route::post('memorial-journals/{number}/decisions', [MemorialJournalWriteController::class, 'decide'])
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->name('memorial-journals.decisions.store');
        Route::delete('memorial-journals/{number}', [MemorialJournalWriteController::class, 'destroy'])
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware($manageMemorialJournals)
            ->name('memorial-journals.destroy');
        Route::post('memorial-journals/{number}/images', [MemorialJournalAttachmentController::class, 'store'])
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware($manageMemorialJournals)
            ->name('memorial-journals.images.store');

        // Master data spreadsheet imports ([ESB-032], S4). esb.invoke here;
        // the module's own manage permission is checked per kind by the
        // controller and the form request. An unknown kind is a 404.
        Route::get('imports/{kind}', [MasterDataImportController::class, 'create'])
            ->middleware('can:'.Permission::InvokeEsbOperations->value)
            ->name('imports.create');
        Route::post('imports/{kind}', [MasterDataImportController::class, 'store'])
            ->name('imports.store');

        // Master data writes (TODO-modules.md §2, S2): esb.invoke and the
        // module's own manage permission. One write per record at a time.
        Route::delete('units/{uomId}', [UnitWriteController::class, 'destroy'])
            ->where('uomId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageUnits->value])
            ->name('units.destroy');
        Route::patch('units/{uomId}/restore', [UnitWriteController::class, 'restore'])
            ->where('uomId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageUnits->value])
            ->name('units.restore');
        Route::delete('product-categories/{categoryId}', [ProductCategoryWriteController::class, 'destroy'])
            ->where('categoryId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageProductCategories->value])
            ->name('product-categories.destroy');
        Route::patch('product-categories/{categoryId}/deactivate', [ProductCategoryWriteController::class, 'deactivate'])
            ->where('categoryId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageProductCategories->value])
            ->name('product-categories.deactivate');
        Route::patch('product-categories/{categoryId}/restore', [ProductCategoryWriteController::class, 'restore'])
            ->where('categoryId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageProductCategories->value])
            ->name('product-categories.restore');
        Route::delete('product-sub-categories/{subCategoryId}', [ProductSubCategoryWriteController::class, 'destroy'])
            ->where('subCategoryId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageProductSubCategories->value])
            ->name('product-sub-categories.destroy');
        Route::patch('product-sub-categories/{subCategoryId}/deactivate', [ProductSubCategoryWriteController::class, 'deactivate'])
            ->where('subCategoryId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageProductSubCategories->value])
            ->name('product-sub-categories.deactivate');
        Route::patch('product-sub-categories/{subCategoryId}/restore', [ProductSubCategoryWriteController::class, 'restore'])
            ->where('subCategoryId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageProductSubCategories->value])
            ->name('product-sub-categories.restore');
        Route::delete('supplier-categories/{supplierCategoryId}', [SupplierCategoryWriteController::class, 'destroy'])
            ->where('supplierCategoryId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageSupplierCategories->value])
            ->name('supplier-categories.destroy');
        Route::patch('supplier-categories/{supplierCategoryId}/restore', [SupplierCategoryWriteController::class, 'restore'])
            ->where('supplierCategoryId', '[1-9][0-9]{0,9}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageSupplierCategories->value])
            ->name('supplier-categories.restore');

        // The account number shares the upstream segment with literal vendor
        // paths (/accounting/coa/export, /index, /tax), so it must start with a
        // digit ([ESB-025]).
        Route::get('coa-accounts', [CoaAccountController::class, 'index'])
            ->name('coa-accounts.index');
        Route::get('coa-accounts/{coaNo}', [CoaAccountController::class, 'show'])
            ->where('coaNo', '[0-9][0-9A-Za-z._-]{0,31}')
            ->middleware('can:'.Permission::InvokeEsbOperations->value)
            ->name('coa-accounts.show');
        Route::patch('coa-accounts/{coaNo}/deactivate', [CoaAccountWriteController::class, 'deactivate'])
            ->where('coaNo', '[0-9][0-9A-Za-z._-]{0,31}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageCoa->value])
            ->name('coa-accounts.deactivate');
        Route::patch('coa-accounts/{coaNo}/restore', [CoaAccountWriteController::class, 'restore'])
            ->where('coaNo', '[0-9][0-9A-Za-z._-]{0,31}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageCoa->value])
            ->name('coa-accounts.restore');
        Route::patch('coa-accounts/{coaNo}/move', [CoaAccountWriteController::class, 'move'])
            ->where('coaNo', '[0-9][0-9A-Za-z._-]{0,31}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManageCoa->value])
            ->name('coa-accounts.move');

        // [ESB-027]. Authorization (esb.invoke) is on the form request for the
        // lists and on the route for the detail. The controller also requires
        // a digit in the number: /pricelist/temp/export is a vendor path.
        Route::get('pricelists', [PricelistController::class, 'index'])
            ->name('pricelists.index');
        Route::get('pricelists/temp', [PricelistController::class, 'tempIndex'])
            ->name('pricelists.temp.index');
        // Prices that help fill a pricelist ([ESB-032]); esb.invoke on the requests.
        Route::get('pricelists/last-prices', [PricelistPriceController::class, 'lastPrices'])
            ->name('pricelists.last-prices');
        Route::get('pricelists/purchase-history', [PricelistPriceController::class, 'purchasePrices'])
            ->name('pricelists.purchase-history');
        // Proposing and editing pricelists ([ESB-031]).
        Route::get('pricelists/create', [PricelistFormController::class, 'create'])
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManagePricelists->value])
            ->name('pricelists.create');
        Route::post('pricelists', [PricelistFormController::class, 'store'])
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManagePricelists->value])
            ->name('pricelists.store');
        Route::get('pricelists/temp/{pricelistNum}/edit', [PricelistFormController::class, 'edit'])
            ->where('pricelistNum', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManagePricelists->value])
            ->name('pricelists.temp.edit');
        Route::put('pricelists/temp/{pricelistNum}', [PricelistFormController::class, 'update'])
            ->where('pricelistNum', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManagePricelists->value])
            ->name('pricelists.temp.update');
        Route::get('pricelists/temp/{pricelistNum}', [PricelistController::class, 'tempShow'])
            ->where('pricelistNum', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware('can:'.Permission::InvokeEsbOperations->value)
            ->name('pricelists.temp.show');
        // Authorization: esb.invoke + pricelists.approve on the form request
        // for decisions; esb.invoke + pricelists.manage here for a delete.
        Route::post('pricelists/temp/{pricelistNum}/decisions', [TempPricelistWriteController::class, 'decide'])
            ->where('pricelistNum', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->name('pricelists.temp.decisions.store');
        Route::delete('pricelists/temp/{pricelistNum}', [TempPricelistWriteController::class, 'destroy'])
            ->where('pricelistNum', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware(['can:'.Permission::InvokeEsbOperations->value, 'can:'.Permission::ManagePricelists->value])
            ->name('pricelists.temp.destroy');

        // Read-only ESB Core document templates ([ESB-016]). Authorization
        // for the list is on its form request (esb.invoke). The id is
        // interpolated into the upstream path, so it is held to a positive
        // integer here.
        Route::get('document-templates', [DocumentTemplateController::class, 'index'])
            ->name('document-templates.index');

        // Creating a template decides what every assigned branch can
        // request, so esb.invoke alone does not grant it ([ESB-016]).
        // Registered before document-templates/{templateId}.
        Route::middleware([
            'can:'.Permission::InvokeEsbOperations->value,
            'can:'.Permission::ManageDocumentTemplates->value,
        ])->group(function () {
            Route::get('document-templates/create', [DocumentTemplateFormController::class, 'create'])
                ->name('document-templates.create');
            Route::post('document-templates', [DocumentTemplateFormController::class, 'store'])
                ->name('document-templates.store');

            // Spreadsheet import (ADR-023): stored in the gateway first,
            // validated against the ERP, then synced.
            Route::get('document-templates/imports', [DocumentTemplateImportController::class, 'index'])
                ->name('document-template-imports.index');
            Route::post('document-templates/imports', [DocumentTemplateImportController::class, 'store'])
                ->name('document-template-imports.store');
            Route::get('document-templates/imports/template', [DocumentTemplateImportController::class, 'template'])
                ->name('document-template-imports.template');
            Route::get('document-templates/imports/{import}', [DocumentTemplateImportController::class, 'show'])
                ->whereNumber('import')
                ->name('document-template-imports.show');
            Route::post('document-templates/imports/{import}/validate', [DocumentTemplateImportController::class, 'startValidation'])
                ->whereNumber('import')
                ->name('document-template-imports.validate');
            Route::post('document-templates/imports/{import}/sync', [DocumentTemplateImportController::class, 'startSync'])
                ->whereNumber('import')
                ->name('document-template-imports.sync');
        });

        Route::get('document-templates/{templateId}', [DocumentTemplateController::class, 'show'])
            ->where('templateId', '[1-9][0-9]{0,9}')
            ->middleware('can:'.Permission::InvokeEsbOperations->value)
            ->name('document-templates.show');

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

        // Authorization for the list is on its form request (esb.invoke).
        Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])
            ->name('purchase-orders.index');

        // Held to a plain document number for the same reason as purchase
        // requests: it is interpolated into the upstream path.
        Route::get('purchase-orders/{number}', [PurchaseOrderController::class, 'show'])
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware('can:'.Permission::InvokeEsbOperations->value)
            ->name('purchase-orders.show');

        Route::delete('purchase-orders/{number}', [PurchaseOrderController::class, 'destroy'])
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware([
                'can:'.Permission::InvokeEsbOperations->value,
                'can:'.Permission::ManagePurchaseOrders->value,
            ])
            ->name('purchase-orders.destroy');

        // Authorization (esb.invoke plus purchase-orders.approve) is on the
        // form request.
        Route::post('purchase-orders/{number}/decisions', [PurchaseOrderDecisionController::class, 'store'])
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->name('purchase-orders.decisions.store');

        // Authorization for the list is on its form request (esb.invoke).
        Route::get('simple-purchases', [SimplePurchaseController::class, 'index'])
            ->name('simple-purchases.index');

        // Held to a plain document number for the same reason as purchase
        // requests: it is interpolated into the upstream path.
        Route::get('simple-purchases/{number}', [SimplePurchaseController::class, 'show'])
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware('can:'.Permission::InvokeEsbOperations->value)
            ->name('simple-purchases.show');

        Route::delete('simple-purchases/{number}', [SimplePurchaseController::class, 'destroy'])
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->middleware([
                'can:'.Permission::InvokeEsbOperations->value,
                'can:'.Permission::ManageSimplePurchases->value,
            ])
            ->name('simple-purchases.destroy');

        // Authorization (esb.invoke plus simple-purchases.approve) is on the
        // form request.
        Route::post('simple-purchases/{number}/decisions', [SimplePurchaseDecisionController::class, 'store'])
            ->where('number', '[A-Za-z0-9][A-Za-z0-9_-]{0,63}')
            ->name('simple-purchases.decisions.store');

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
