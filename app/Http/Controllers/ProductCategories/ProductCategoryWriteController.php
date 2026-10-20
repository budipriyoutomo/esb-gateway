<?php

namespace App\Http\Controllers\ProductCategories;

use App\Actions\ProductCategories\ProductCategoryWrites;
use App\Actions\Support\RecordOperation;
use App\Http\Controllers\Concerns\HandlesRecordWrites;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Writes on an existing product category ([ESB-021], S2). Authorization
 * (esb.invoke + the module's manage permission) is on the routes.
 */
class ProductCategoryWriteController extends Controller
{
    use HandlesRecordWrites;

    public function destroy(int $categoryId, Request $request, ProductCategoryWrites $writes): RedirectResponse
    {
        return $this->recordWriteOutcome(
            label: 'Product category',
            name: $this->recordName($request, $categoryId),
            routePrefix: 'product-categories',
            id: $categoryId,
            operation: RecordOperation::Delete,
            write: fn () => $writes->delete($categoryId),
        );
    }

    public function deactivate(int $categoryId, Request $request, ProductCategoryWrites $writes): RedirectResponse
    {
        return $this->recordWriteOutcome(
            label: 'Product category',
            name: $this->recordName($request, $categoryId),
            routePrefix: 'product-categories',
            id: $categoryId,
            operation: RecordOperation::Deactivate,
            write: fn () => $writes->deactivate($categoryId),
        );
    }

    public function restore(int $categoryId, Request $request, ProductCategoryWrites $writes): RedirectResponse
    {
        return $this->recordWriteOutcome(
            label: 'Product category',
            name: $this->recordName($request, $categoryId),
            routePrefix: 'product-categories',
            id: $categoryId,
            operation: RecordOperation::Restore,
            write: fn () => $writes->restore($categoryId),
        );
    }
}
