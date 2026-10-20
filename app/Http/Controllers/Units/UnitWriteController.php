<?php

namespace App\Http\Controllers\Units;

use App\Actions\Support\RecordOperation;
use App\Actions\Units\UnitWrites;
use App\Http\Controllers\Concerns\HandlesRecordWrites;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Writes on an existing unit ([ESB-020], S2). Authorization
 * (esb.invoke + the module's manage permission) is on the routes.
 */
class UnitWriteController extends Controller
{
    use HandlesRecordWrites;

    public function destroy(int $uomId, Request $request, UnitWrites $writes): RedirectResponse
    {
        return $this->recordWriteOutcome(
            label: 'Unit',
            name: $this->recordName($request, $uomId),
            routePrefix: 'units',
            id: $uomId,
            operation: RecordOperation::Delete,
            write: fn () => $writes->delete($uomId),
        );
    }

    public function restore(int $uomId, Request $request, UnitWrites $writes): RedirectResponse
    {
        return $this->recordWriteOutcome(
            label: 'Unit',
            name: $this->recordName($request, $uomId),
            routePrefix: 'units',
            id: $uomId,
            operation: RecordOperation::Restore,
            write: fn () => $writes->restore($uomId),
        );
    }
}
