<?php

namespace App\Http\Controllers\PurchaseRequests;

use App\Actions\PurchaseRequests\CreatePurchaseRequest;
use App\Actions\PurchaseRequests\DeletePurchaseRequest;
use App\Actions\PurchaseRequests\UpdatePurchaseRequest;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Domain\Canonical\PurchaseRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequests\PurchaseRequestFormQuery;
use App\Http\Requests\PurchaseRequests\SavePurchaseRequestRequest;
use App\Integrations\Contracts\PurchaseRequestSource;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Create, edit, and delete purchase requests.
 *
 * The screen's lookups are closures so an Inertia partial reload -- picking a
 * branch, then a template -- asks the ESB only for what changed, not for the
 * branch list again.
 *
 * Where each outcome lands:
 * - success: the document (or the list, after a delete);
 * - a definite refusal of a create/update: back to the form, with the ESB's
 *   field errors on the fields they name, so nothing typed is lost;
 * - an unconfirmed write: wherever the user can see what actually happened --
 *   the document after an update, the list after a create (no number yet) or a
 *   delete (the document may be gone).
 */
class PurchaseRequestFormController extends Controller
{
    /**
     * The vendor's request field names, and the form fields they belong to.
     * An error on anything else is shown for the form as a whole.
     */
    private const FIELD_MAP = [
        'branchID' => 'branchId',
        'purchaseRequestDate' => 'date',
        'requiredDate' => 'requiredDate',
        'requestTemplateID' => 'templateId',
        'additionalInfo' => 'notes',
        'purchaseRequestDetails' => 'lines',
    ];

    public function create(PurchaseRequestFormQuery $query, PurchaseRequestSource $purchaseRequests): Response
    {
        return $this->form($purchaseRequests, null, $query->branchId(), $query->templateId());
    }

    public function edit(string $number, PurchaseRequestFormQuery $query, PurchaseRequestSource $purchaseRequests): Response
    {
        $purchaseRequest = $purchaseRequests->findPurchaseRequest($number);

        return $this->form(
            $purchaseRequests,
            $purchaseRequest,
            $query->branchId() ?? $purchaseRequest->branchId,
            $query->templateId(),
        );
    }

    public function store(SavePurchaseRequestRequest $request, CreatePurchaseRequest $create): RedirectResponse
    {
        try {
            $number = $create->handle($request->toDraft());
        } catch (WriteAlreadyInFlight) {
            $this->toast('warning', 'Your previous purchase request is still being created. Check the list before trying again.');

            return redirect()->route('purchase-requests.index');
        } catch (EsbException $exception) {
            if ($exception->mayHaveBeenApplied()) {
                $this->toast('warning', sprintf(
                    'The ERP did not confirm the new purchase request. It may or may not have been created -- check the list before trying again. (reference: %s)',
                    $exception->context->correlationId,
                ));

                return redirect()->route('purchase-requests.index');
            }

            throw $this->asFormErrors($exception);
        }

        if ($number === null) {
            $this->toast('success', 'The purchase request was created.');

            return redirect()->route('purchase-requests.index');
        }

        $this->toast('success', sprintf('Purchase request %s was created.', $number));

        return redirect()->route('purchase-requests.show', $number);
    }

    public function update(string $number, SavePurchaseRequestRequest $request, UpdatePurchaseRequest $update): RedirectResponse
    {
        try {
            $update->handle($number, $request->toDraft());

            $this->toast('success', sprintf('Purchase request %s was updated.', $number));
        } catch (WriteAlreadyInFlight) {
            $this->toast('warning', sprintf('Another change to %s is still being processed. Check it before trying again.', $number));
        } catch (EsbNotFoundException $exception) {
            throw $exception;
        } catch (EsbException $exception) {
            if (! $exception->mayHaveBeenApplied()) {
                throw $this->asFormErrors($exception);
            }

            $this->toast('warning', sprintf(
                'The ERP did not confirm the update to %s. It may or may not have been applied -- check it below before trying again. (reference: %s)',
                $number,
                $exception->context->correlationId,
            ));
        }

        return redirect()->route('purchase-requests.show', $number);
    }

    public function destroy(string $number, DeletePurchaseRequest $delete): RedirectResponse
    {
        try {
            $delete->handle($number);
        } catch (WriteAlreadyInFlight) {
            $this->toast('warning', sprintf('Another change to %s is still being processed. Check it before trying again.', $number));

            return redirect()->route('purchase-requests.show', $number);
        } catch (EsbNotFoundException $exception) {
            throw $exception;
        } catch (EsbException $exception) {
            if ($exception->mayHaveBeenApplied()) {
                $this->toast('warning', sprintf(
                    'The ERP did not confirm deleting %s. It may or may not be gone -- check the list before trying again. (reference: %s)',
                    $number,
                    $exception->context->correlationId,
                ));

                return redirect()->route('purchase-requests.index');
            }

            $this->toast('error', sprintf('Could not delete %s. %s', $number, $exception->safeMessage()));

            return redirect()->route('purchase-requests.show', $number);
        }

        $this->toast('success', sprintf('Purchase request %s was deleted.', $number));

        return redirect()->route('purchase-requests.index');
    }

    private function form(
        PurchaseRequestSource $purchaseRequests,
        ?PurchaseRequest $purchaseRequest,
        ?int $branchId,
        ?int $templateId,
    ): Response {
        return Inertia::render('purchase-requests/form', [
            'purchaseRequest' => $purchaseRequest,
            'branches' => fn (): array => $purchaseRequests->listBranches(),
            'templates' => fn (): array => $branchId === null
                ? []
                : $purchaseRequests->listRequestTemplates($branchId),
            'templateLines' => fn (): array => $branchId === null || $templateId === null
                ? []
                : $purchaseRequests->listTemplateLines($branchId, $templateId),
            'selection' => ['branchId' => $branchId, 'templateId' => $templateId],
        ]);
    }

    /**
     * A definite refusal of a create or update, as validation errors on the
     * form. The ESB's per-field messages are meant for whoever filled the form;
     * without any, the safe summary (with its reference) is shown instead.
     */
    private function asFormErrors(EsbException $exception): ValidationException
    {
        $messages = [];

        foreach ($exception->fieldErrors as $fieldError) {
            $key = self::FIELD_MAP[$fieldError->field ?? ''] ?? 'form';
            $messages[$key][] = $fieldError->message;
        }

        if ($messages === []) {
            $messages['form'] = [$exception->safeMessage()];
        }

        // Inertia shares one message per field, so several for the same
        // field are joined rather than all but the first dropped.
        return ValidationException::withMessages(array_map(
            static fn (array $forField): string => implode(' ', array_unique($forField)),
            $messages,
        ));
    }
}
