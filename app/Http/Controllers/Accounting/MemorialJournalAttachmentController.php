<?php

namespace App\Http\Controllers\Accounting;

use App\Actions\MemorialJournals\MemorialJournalAttachments;
use App\Http\Controllers\Concerns\HandlesDocumentWrites;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\AttachMemorialJournalImageRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Attach an image to a memorial journal ([ESB-046], S4). Authorization
 * (esb.invoke + memorial-journals.manage) is on the route and the form
 * request. The vendor adds the image to the journal's asset images, so
 * nothing is read first.
 */
class MemorialJournalAttachmentController extends Controller
{
    use HandlesDocumentWrites;

    public function store(string $number, AttachMemorialJournalImageRequest $request, MemorialJournalAttachments $attachments): RedirectResponse
    {
        MemorialJournalController::assertUsableNumber($number);

        ['contents' => $contents, 'filename' => $filename] = $request->attachment();

        return $this->transitionOutcome(
            'Memorial journal', 'memorial-journals', $number, 'attach an image to', 'given a new image',
            fn () => $attachments->attach($number, $contents, $filename),
            sprintf('%s was attached to %s.', $filename, $number),
        );
    }
}
