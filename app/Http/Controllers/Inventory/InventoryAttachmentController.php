<?php

namespace App\Http\Controllers\Inventory;

use App\Actions\Inventory\InventoryAttachments;
use App\Http\Concerns\ResolvesInventoryKind;
use App\Http\Controllers\Concerns\HandlesDocumentWrites;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\AttachInventoryFilesRequest;
use App\Integrations\Contracts\InventoryDocumentSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Attach files to an inventory document, or remove all of them ([ESB-036],
 * S4). Authorization (esb.invoke + <kind>.manage) is on the routes and the
 * form request. The outcome lands on the document.
 */
class InventoryAttachmentController extends Controller
{
    use HandlesDocumentWrites;
    use ResolvesInventoryKind;

    public function store(
        string $number,
        AttachInventoryFilesRequest $request,
        InventoryDocumentSource $documents,
        InventoryAttachments $attachments,
    ): RedirectResponse {
        $this->assertUsableNumber($number);

        $kind = $request->kind();
        $files = $request->attachments();

        if ($kind->attachmentUploadReplaces()) {
            // The vendor keeps only the "existing" URLs it is sent, which the
            // gateway does not hold: attaching while there are attachments
            // would drop them. A read, before the guarded write.
            $document = $documents->findDocument($kind, $number);

            abort_if($document === null, 404);

            if ($document->attachmentCount > 0) {
                $this->toast('warning', sprintf(
                    '%s %s already has attachments, and the ERP would replace them with the new files. %s',
                    $kind->label(),
                    $number,
                    $kind->attachmentsRemovable()
                        ? 'Remove the current attachments first, then attach every file again.'
                        : 'Add the files in the ERP.',
                ));

                return redirect()->route($kind->value.'.show', $number);
            }
        }

        return $this->transitionOutcome(
            $kind->label(), $kind->value, $number, 'attach files to', 'given new attachments',
            fn () => $attachments->attach($kind, $number, $files),
            sprintf(count($files) === 1 ? '%d file was attached to %s.' : '%d files were attached to %s.', count($files), $number),
        );
    }

    public function destroy(string $number, Request $request, InventoryAttachments $attachments): RedirectResponse
    {
        $this->assertUsableNumber($number);

        $kind = $this->inventoryKind($request);

        return $this->transitionOutcome(
            $kind->label(), $kind->value, $number, 'remove the attachments of', 'cleared of attachments',
            fn () => $attachments->delete($kind, $number),
            sprintf('Every attachment of %s was removed.', $number),
        );
    }

    private function assertUsableNumber(string $number): void
    {
        abort_unless(preg_match(InventoryDocumentController::NUMBER, $number) === 1, 404);
    }
}
