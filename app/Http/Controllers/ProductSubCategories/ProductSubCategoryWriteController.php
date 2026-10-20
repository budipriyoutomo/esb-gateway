<?php

namespace App\Http\Controllers\ProductSubCategories;

use App\Actions\ProductSubCategories\ProductSubCategoryWrites;
use App\Actions\Support\RecordOperation;
use App\Http\Controllers\Concerns\HandlesRecordWrites;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Writes on an existing product sub-category ([ESB-022], S2). Authorization
 * (esb.invoke + the module's manage permission) is on the routes.
 */
class ProductSubCategoryWriteController extends Controller
{
    use HandlesRecordWrites;

    public function destroy(int $subCategoryId, Request $request, ProductSubCategoryWrites $writes): RedirectResponse
    {
        return $this->recordWriteOutcome(
            label: 'Product sub-category',
            name: $this->recordName($request, $subCategoryId),
            routePrefix: 'product-sub-categories',
            id: $subCategoryId,
            operation: RecordOperation::Delete,
            write: fn () => $writes->delete($subCategoryId),
        );
    }

    public function deactivate(int $subCategoryId, Request $request, ProductSubCategoryWrites $writes): RedirectResponse
    {
        return $this->recordWriteOutcome(
            label: 'Product sub-category',
            name: $this->recordName($request, $subCategoryId),
            routePrefix: 'product-sub-categories',
            id: $subCategoryId,
            operation: RecordOperation::Deactivate,
            write: fn () => $writes->deactivate($subCategoryId),
        );
    }

    public function restore(int $subCategoryId, Request $request, ProductSubCategoryWrites $writes): RedirectResponse
    {
        return $this->recordWriteOutcome(
            label: 'Product sub-category',
            name: $this->recordName($request, $subCategoryId),
            routePrefix: 'product-sub-categories',
            id: $subCategoryId,
            operation: RecordOperation::Restore,
            write: fn () => $writes->restore($subCategoryId),
        );
    }
}
