<?php

namespace App\Http\Controllers\Accounting;

use App\Actions\MemorialJournals\DecideMemorialJournal;
use App\Actions\MemorialJournals\DeleteMemorialJournal;
use App\Http\Controllers\Concerns\HandlesDocumentWrites;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\DecideMemorialJournalRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Authorize / reject / delete a memorial journal ([ESB-044]). The
 * permissions are checked by the form request (decisions) or the route
 * (delete). Every outcome lands where the person can see the journal as the
 * ERP now has it.
 */
class MemorialJournalWriteController extends Controller
{
    use HandlesDocumentWrites;

    public function decide(string $number, DecideMemorialJournalRequest $request, DecideMemorialJournal $decide): RedirectResponse
    {
        MemorialJournalController::assertUsableNumber($number);

        $decision = $request->decision();

        return $this->decisionOutcome(
            'Memorial journal', 'memorial-journals', $number, $decision,
            fn () => $decide->handle($number, $decision, $request->reason()),
        );
    }

    public function destroy(string $number, DeleteMemorialJournal $delete): RedirectResponse
    {
        MemorialJournalController::assertUsableNumber($number);

        return $this->deleteOutcome('Memorial journal', 'memorial-journals', $number, fn () => $delete->handle($number));
    }
}
