<?php

namespace App\Http\Controllers\PurchaseRequests;

use App\Actions\PurchaseRequests\ImportPurchaseRequests;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequests\ImportPurchaseRequestsRequest;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Responses\EsbFieldError;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Import purchase requests from an Excel file into the ERP.
 *
 * The ESB answers an import with a plain envelope and no list of created
 * documents, so success lands on the list, where the new requests appear. A
 * refusal lands back on the import screen with the ESB's per-row errors.
 */
class PurchaseRequestImportController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('purchase-requests/import', [
            'maxKilobytes' => ImportPurchaseRequestsRequest::MAX_KILOBYTES,
        ]);
    }

    public function store(ImportPurchaseRequestsRequest $request, ImportPurchaseRequests $import): RedirectResponse
    {
        $filename = $request->safeFilename();

        try {
            $import->handle($request->contents(), $filename);
        } catch (WriteAlreadyInFlight) {
            $this->toast('warning', 'Your previous import is still being processed. Check the list before importing again.');

            return redirect()->route('purchase-requests.index');
        } catch (EsbException $exception) {
            if ($exception->mayHaveBeenApplied()) {
                $this->toast('warning', sprintf(
                    'The ERP did not confirm the import of %s. Some or all requests may have been created -- check the list before importing again. (reference: %s)',
                    $filename,
                    $exception->context->correlationId,
                ));

                return redirect()->route('purchase-requests.index');
            }

            $this->toast('error', sprintf('The ERP refused %s. %s', $filename, $exception->safeMessage()));

            Inertia::flash('importErrors', array_map(
                static fn (EsbFieldError $error): array => $error->toArray(),
                $exception->fieldErrors,
            ));

            return redirect()->route('purchase-requests.import.create');
        }

        $this->toast('success', sprintf('%s was imported.', $filename));

        return redirect()->route('purchase-requests.index');
    }
}
