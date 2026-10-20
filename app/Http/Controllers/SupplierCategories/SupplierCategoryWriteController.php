<?php

namespace App\Http\Controllers\SupplierCategories;

use App\Actions\SupplierCategories\SupplierCategoryWrites;
use App\Actions\Support\RecordOperation;
use App\Http\Controllers\Concerns\HandlesRecordWrites;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Writes on an existing supplier category ([ESB-023], S2). Authorization
 * (esb.invoke + the module's manage permission) is on the routes.
 */
class SupplierCategoryWriteController extends Controller
{
    use HandlesRecordWrites;

    public function destroy(int $supplierCategoryId, Request $request, SupplierCategoryWrites $writes): RedirectResponse
    {
        return $this->recordWriteOutcome(
            label: 'Supplier category',
            name: $this->recordName($request, $supplierCategoryId),
            routePrefix: 'supplier-categories',
            id: $supplierCategoryId,
            operation: RecordOperation::Delete,
            write: fn () => $writes->delete($supplierCategoryId),
        );
    }

    public function restore(int $supplierCategoryId, Request $request, SupplierCategoryWrites $writes): RedirectResponse
    {
        return $this->recordWriteOutcome(
            label: 'Supplier category',
            name: $this->recordName($request, $supplierCategoryId),
            routePrefix: 'supplier-categories',
            id: $supplierCategoryId,
            operation: RecordOperation::Restore,
            write: fn () => $writes->restore($supplierCategoryId),
        );
    }
}
