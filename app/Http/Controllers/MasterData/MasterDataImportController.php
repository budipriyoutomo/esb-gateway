<?php

namespace App\Http\Controllers\MasterData;

use App\Actions\MasterData\ImportMasterData;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Authorization\Permission;
use App\Domain\Canonical\MasterDataImport;
use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\ImportMasterDataRequest;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Responses\EsbFieldError;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The import screen for one kind of master data ([ESB-032], S4). Success
 * and an unconfirmed import land on the module's list; a refusal comes back
 * here with the ERP's per-row errors.
 */
class MasterDataImportController extends Controller
{
    /** Where each kind's records are listed. */
    public static function listRoute(MasterDataImport $kind): string
    {
        return match ($kind) {
            MasterDataImport::Units => 'units.index',
            MasterDataImport::ProductCategories => 'product-categories.index',
            MasterDataImport::ProductSubCategories => 'product-sub-categories.index',
            MasterDataImport::NewProducts, MasterDataImport::ProductChanges => 'products.index',
            MasterDataImport::Pricelists => 'pricelists.index',
            MasterDataImport::NewSuppliers, MasterDataImport::SupplierChanges => 'suppliers.index',
            MasterDataImport::CoaAccounts => 'coa-accounts.index',
            MasterDataImport::SimpleTransfers => 'simple-transfers.index',
        };
    }

    public function create(MasterDataImport $kind, Request $request): Response
    {
        $user = $request->user();
        abort_unless(
            $user !== null
                && $user->can(Permission::InvokeEsbOperations->value)
                && $user->can(ImportMasterDataRequest::permissionFor($kind)->value),
            403,
        );

        return Inertia::render('imports/create', [
            'import' => [
                'kind' => $kind->value,
                'label' => $kind->label(),
                'extensions' => $kind->extensions(),
                'queued' => $kind->queued(),
                'maxKilobytes' => ImportMasterDataRequest::MAX_KILOBYTES,
                'listUrl' => route(self::listRoute($kind)),
            ],
        ]);
    }

    public function store(MasterDataImport $kind, ImportMasterDataRequest $request, ImportMasterData $import): RedirectResponse
    {
        $filename = $request->safeFilename();
        $list = redirect()->route(self::listRoute($kind));

        try {
            $saved = $import->handle($kind, $request->contents(), $filename);
        } catch (WriteAlreadyInFlight) {
            $this->toast('warning', sprintf('Your previous import of %s is still being processed. Check the list before importing again.', $kind->label()));

            return $list;
        } catch (EsbException $exception) {
            if ($exception->mayHaveBeenApplied()) {
                $this->toast('warning', sprintf(
                    'The ERP did not confirm the import of %s. Some or all rows may have been imported -- check the list before importing again. (reference: %s)',
                    $filename,
                    $exception->context->correlationId,
                ));

                return $list;
            }

            $this->toast('error', sprintf('The ERP refused %s. %s', $filename, $exception->safeMessage()));

            Inertia::flash('importErrors', array_map(
                static fn (EsbFieldError $error): array => $error->toArray(),
                $exception->fieldErrors,
            ));

            return redirect()->route('imports.create', $kind->value);
        }

        $this->toast('success', match (true) {
            $kind->queued() => sprintf('%s was handed to the ERP, which imports it in the background. Check the list in a few minutes: rows the ERP rejects later are not reported here.', $filename),
            isset($saved['accounts'], $saved['mappings']) => sprintf('%s was imported: %d accounts and %d account mappings.', $filename, $saved['accounts'], $saved['mappings']),
            default => sprintf('%s was imported.', $filename),
        });

        return $list;
    }
}
