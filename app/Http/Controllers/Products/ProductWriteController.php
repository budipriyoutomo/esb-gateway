<?php

namespace App\Http\Controllers\Products;

use App\Actions\Products\ProductWrites;
use App\Actions\Support\RecordOperation;
use App\Http\Controllers\Concerns\HandlesRecordWrites;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Delete or restore an existing product ([ESB-029], S2). Authorization
 * (esb.invoke + products.manage) is on the routes.
 */
class ProductWriteController extends Controller
{
    use HandlesRecordWrites;

    public function destroy(int $productId, Request $request, ProductWrites $writes): RedirectResponse
    {
        return $this->recordWriteOutcome(
            label: 'Product',
            name: $this->recordName($request, $productId),
            routePrefix: 'products',
            id: $productId,
            operation: RecordOperation::Delete,
            write: fn () => $writes->delete($productId),
        );
    }

    public function restore(int $productId, Request $request, ProductWrites $writes): RedirectResponse
    {
        return $this->recordWriteOutcome(
            label: 'Product',
            name: $this->recordName($request, $productId),
            routePrefix: 'products',
            id: $productId,
            operation: RecordOperation::Restore,
            write: fn () => $writes->restore($productId),
        );
    }
}
