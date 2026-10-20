<?php

namespace App\Http\Controllers\Inventory;

use App\Actions\Inventory\InventoryDocumentForms;
use App\Domain\Canonical\InventoryDocument;
use App\Domain\Canonical\InventoryKind;
use App\Http\Concerns\ResolvesInventoryKind;
use App\Http\Controllers\Concerns\HandlesRecordForms;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\SaveInventoryDocumentRequest;
use App\Integrations\Contracts\InventoryDocumentSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use LogicException;

/**
 * Create and edit goods transfer requests, item journals and simple
 * transfers ([ESB-035], S3). The kind is a route default; authorization
 * (esb.invoke + <kind>.manage) is on the routes and the form request.
 *
 * An edit reads the document first: the ERP may say it can no longer be
 * edited, and a document that moves assets is left to the ERP -- the form
 * has no assets, and sending it would drop them.
 */
class InventoryDocumentFormController extends Controller
{
    use HandlesRecordForms;
    use ResolvesInventoryKind;

    public function create(Request $request): Response
    {
        $kind = $this->inventoryKind($request);

        return Inertia::render('inventory/form', [
            'kind' => InventoryDocumentController::describe($kind),
            'document' => null,
        ]);
    }

    public function store(SaveInventoryDocumentRequest $request, InventoryDocumentForms $forms): RedirectResponse
    {
        $kind = $request->kind();
        $draft = $request->toDraft();

        return $this->recordSaveOutcome(
            $kind->label(),
            'of '.$draft->date,
            $kind->value,
            null,
            fn (): ?string => $forms->create($kind, $draft),
            self::fieldMap($kind),
        );
    }

    public function edit(string $number, Request $request, InventoryDocumentSource $documents): Response|RedirectResponse
    {
        $kind = $this->inventoryKind($request);
        $document = $this->editable($kind, $number, $documents);

        if ($document instanceof RedirectResponse) {
            return $document;
        }

        return Inertia::render('inventory/form', [
            'kind' => InventoryDocumentController::describe($kind),
            'document' => $document,
        ]);
    }

    public function update(
        string $number,
        SaveInventoryDocumentRequest $request,
        InventoryDocumentSource $documents,
        InventoryDocumentForms $forms,
    ): RedirectResponse {
        $kind = $request->kind();
        $draft = $request->toDraft();

        // A read, before the guarded write: its failure is never mistaken for
        // an unconfirmed update.
        $current = $this->editable($kind, $number, $documents);

        if ($current instanceof RedirectResponse) {
            return $current;
        }

        return $this->recordSaveOutcome(
            $kind->label(),
            $number,
            $kind->value,
            $number,
            fn () => $forms->update($kind, $number, $draft, $current),
            self::fieldMap($kind),
        );
    }

    /**
     * The document, or the way back to it when it is not edited here.
     */
    private function editable(InventoryKind $kind, string $number, InventoryDocumentSource $documents): InventoryDocument|RedirectResponse
    {
        abort_unless(preg_match(InventoryDocumentController::NUMBER, $number) === 1, 404);

        $document = $documents->findDocument($kind, $number);

        abort_if($document === null, 404);

        $reason = match (true) {
            $document->carryOver->hasAssets => 'it moves assets, which the gateway does not edit',
            $document->canEdit === false => 'the ERP no longer allows it to be edited',
            default => null,
        };

        if ($reason === null) {
            return $document;
        }

        $this->toast('warning', sprintf('%s %s cannot be edited here: %s. Change it in the ERP.', $kind->label(), $number, $reason));

        return redirect()->route($kind->value.'.show', $number);
    }

    /**
     * Vendor request field => form field, for the ERP's field errors
     * (ADR-021). Anything else lands on the form as a whole.
     *
     * @return array<string, string>
     */
    private static function fieldMap(InventoryKind $kind): array
    {
        return match ($kind) {
            InventoryKind::GoodsTransferRequests => [
                'transferDate' => 'date',
                'originBranchID' => 'branchId',
                'originLocationID' => 'locationId',
                'destinationBranchID' => 'destinationBranchId',
                'additionalInfo' => 'notes',
                'transferDetails' => 'lines',
            ],
            InventoryKind::ItemJournals => [
                'itemJournalDate' => 'date',
                'branchID' => 'branchId',
                'locationID' => 'locationId',
                'additionalInfo' => 'notes',
                'itemJournalDetails' => 'lines',
            ],
            InventoryKind::SimpleTransfers => [
                'simpleTransferDate' => 'date',
                'originLocationID' => 'locationId',
                'destinationLocationID' => 'destinationLocationId',
                'additionalInfo' => 'notes',
                'simpleTransferDetails' => 'lines',
            ],
            default => throw new LogicException("{$kind->value} is not created or edited through the gateway."),
        };
    }
}
