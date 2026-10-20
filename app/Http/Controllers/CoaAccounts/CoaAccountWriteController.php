<?php

namespace App\Http\Controllers\CoaAccounts;

use App\Actions\CoaAccounts\CoaAccountWrites;
use App\Actions\Support\RecordOperation;
use App\Http\Controllers\Concerns\HandlesRecordWrites;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Deactivate or restore an existing record ([ESB-025], S2). Authorization
 * (esb.invoke + the module's manage permission) is on the routes.
 */
class CoaAccountWriteController extends Controller
{
    use HandlesRecordWrites;

    public function deactivate(string $coaNo, Request $request, CoaAccountWrites $writes): RedirectResponse
    {
        return $this->recordWriteOutcome(
            label: 'Account',
            name: $this->recordName($request, $coaNo),
            routePrefix: 'coa-accounts',
            id: $coaNo,
            operation: RecordOperation::Deactivate,
            write: fn () => $writes->deactivate($coaNo),
        );
    }

    public function restore(string $coaNo, Request $request, CoaAccountWrites $writes): RedirectResponse
    {
        return $this->recordWriteOutcome(
            label: 'Account',
            name: $this->recordName($request, $coaNo),
            routePrefix: 'coa-accounts',
            id: $coaNo,
            operation: RecordOperation::Restore,
            write: fn () => $writes->restore($coaNo),
        );
    }

    public function move(string $coaNo, Request $request, CoaAccountWrites $writes): RedirectResponse
    {
        /** @var 'up'|'down' $direction */
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        return $this->recordWriteOutcome(
            label: 'Account',
            name: $this->recordName($request, $coaNo),
            routePrefix: 'coa-accounts',
            id: $coaNo,
            operation: RecordOperation::Move,
            write: fn () => $writes->move($coaNo, $direction),
        );
    }
}
