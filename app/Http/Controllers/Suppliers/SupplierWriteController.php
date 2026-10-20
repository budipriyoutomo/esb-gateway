<?php

namespace App\Http\Controllers\Suppliers;

use App\Actions\Suppliers\SupplierWrites;
use App\Actions\Support\RecordOperation;
use App\Http\Controllers\Concerns\HandlesRecordWrites;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Deactivate or restore an existing record ([ESB-024], S2). Authorization
 * (esb.invoke + the module's manage permission) is on the routes.
 */
class SupplierWriteController extends Controller
{
    use HandlesRecordWrites;

    public function deactivate(int $supplierId, Request $request, SupplierWrites $writes): RedirectResponse
    {
        return $this->recordWriteOutcome(
            label: 'Supplier',
            name: $this->recordName($request, $supplierId),
            routePrefix: 'suppliers',
            id: $supplierId,
            operation: RecordOperation::Deactivate,
            write: fn () => $writes->deactivate($supplierId),
        );
    }

    public function restore(int $supplierId, Request $request, SupplierWrites $writes): RedirectResponse
    {
        return $this->recordWriteOutcome(
            label: 'Supplier',
            name: $this->recordName($request, $supplierId),
            routePrefix: 'suppliers',
            id: $supplierId,
            operation: RecordOperation::Restore,
            write: fn () => $writes->restore($supplierId),
        );
    }
}
