<?php

namespace App\Http\Controllers\Disbursements;

use App\Actions\Disbursements\DecideDisbursement;
use App\Actions\Disbursements\DeleteDisbursement;
use App\Http\Controllers\Concerns\HandlesDocumentWrites;
use App\Http\Controllers\Controller;
use App\Http\Requests\Disbursements\DecideDisbursementRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Authorize / reject / delete a disbursement ([ESB-038]). The permissions
 * are checked by the form request (decisions) or the route (delete). Every
 * outcome lands where the person can see the disbursement as the ERP now
 * has it.
 */
class DisbursementWriteController extends Controller
{
    use HandlesDocumentWrites;

    public function decide(string $number, DecideDisbursementRequest $request, DecideDisbursement $decide): RedirectResponse
    {
        DisbursementController::assertUsableNumber($number);

        $decision = $request->decision();

        return $this->decisionOutcome(
            'Disbursement', 'disbursements', $number, $decision,
            fn () => $decide->handle($number, $decision, $request->reason()),
        );
    }

    public function destroy(string $number, DeleteDisbursement $delete): RedirectResponse
    {
        DisbursementController::assertUsableNumber($number);

        return $this->deleteOutcome('Disbursement', 'disbursements', $number, fn () => $delete->handle($number));
    }
}
