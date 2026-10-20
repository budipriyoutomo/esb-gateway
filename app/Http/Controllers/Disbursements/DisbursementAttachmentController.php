<?php

namespace App\Http\Controllers\Disbursements;

use App\Actions\Disbursements\DisbursementAttachments;
use App\Http\Controllers\Concerns\HandlesDocumentWrites;
use App\Http\Controllers\Controller;
use App\Http\Requests\Disbursements\AttachDisbursementFilesRequest;
use App\Integrations\Contracts\DisbursementSource;
use Illuminate\Http\RedirectResponse;

/**
 * Attach files to a disbursement ([ESB-040], S4). Authorization
 * (esb.invoke + disbursements.manage) is on the route and the form request.
 */
class DisbursementAttachmentController extends Controller
{
    use HandlesDocumentWrites;

    public function store(
        string $number,
        AttachDisbursementFilesRequest $request,
        DisbursementSource $disbursements,
        DisbursementAttachments $attachments,
    ): RedirectResponse {
        DisbursementController::assertUsableNumber($number);

        $files = $request->attachments();

        // The vendor keeps only the "existing" references it is sent, which
        // the gateway does not send: attaching while there are attachments
        // would drop them. A read, before the guarded write.
        $disbursement = $disbursements->findDisbursement($number);

        abort_if($disbursement === null, 404);

        if ($disbursement->attachmentCount > 0) {
            $this->toast('warning', sprintf(
                'Disbursement %s already has attachments, and the ERP would replace them with the new files. Add the files in the ERP.',
                $number,
            ));

            return redirect()->route('disbursements.show', $number);
        }

        return $this->transitionOutcome(
            'Disbursement', 'disbursements', $number, 'attach files to', 'given new attachments',
            fn () => $attachments->attach($number, $files),
            sprintf(count($files) === 1 ? '%d file was attached to %s.' : '%d files were attached to %s.', count($files), $number),
        );
    }
}
