<?php

namespace App\Http\Controllers\Disbursements;

use App\Actions\Disbursements\DisbursementForms;
use App\Domain\Canonical\Disbursement;
use App\Http\Controllers\Concerns\HandlesRecordForms;
use App\Http\Controllers\Controller;
use App\Http\Requests\Disbursements\SaveDisbursementRequest;
use App\Integrations\Contracts\DisbursementSource;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbNotFoundException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Create and edit disbursements ([ESB-039], S3). Authorization (esb.invoke
 * + disbursements.manage) is on the routes and the form request.
 *
 * An edit first reads the disbursement and asks the ERP whether it may be
 * changed (PUT .../validate: period, supplier, cost center, payments). Both
 * happen before the guarded write, so neither is ever mistaken for an
 * unconfirmed update.
 */
class DisbursementFormController extends Controller
{
    use HandlesRecordForms;

    /**
     * Vendor request field => form field, for the ERP's field errors
     * (ADR-021). Anything else lands on the form as a whole.
     */
    private const FIELD_MAP = [
        'disbursementDate' => 'date',
        'branchID' => 'branchId',
        'paymentID' => 'paymentId',
        'currencyID' => 'currencyId',
        'rate' => 'rate',
        'disbursementCoaNo' => 'cashBankCoaNo',
        'supplierID' => 'supplierId',
        'creditTerm' => 'creditTerm',
        'ref' => 'reference',
        'invoiceDueDate' => 'invoiceDueDate',
        'additionalInformation' => 'notes',
        'disbursementDetails' => 'lines',
    ];

    public function create(): Response
    {
        return Inertia::render('disbursements/form', ['disbursement' => null]);
    }

    public function store(SaveDisbursementRequest $request, DisbursementForms $forms): RedirectResponse
    {
        $draft = $request->toDraft();

        return $this->recordSaveOutcome(
            'Disbursement',
            'of '.$draft->date,
            'disbursements',
            null,
            fn (): ?string => $forms->create($draft),
            self::FIELD_MAP,
        );
    }

    public function edit(string $number, DisbursementSource $disbursements): Response|RedirectResponse
    {
        $disbursement = $this->editable($number, $disbursements);

        if ($disbursement instanceof RedirectResponse) {
            return $disbursement;
        }

        return Inertia::render('disbursements/form', ['disbursement' => $disbursement]);
    }

    public function update(
        string $number,
        SaveDisbursementRequest $request,
        DisbursementSource $disbursements,
        DisbursementForms $forms,
    ): RedirectResponse {
        $draft = $request->toDraft();
        $current = $this->editable($number, $disbursements);

        if ($current instanceof RedirectResponse) {
            return $current;
        }

        return $this->recordSaveOutcome(
            'Disbursement',
            $number,
            'disbursements',
            $number,
            fn () => $forms->update($number, $draft, $current),
            self::FIELD_MAP,
        );
    }

    /**
     * The disbursement, or the way back to it when the ERP will not let it
     * change. The pre-check saves nothing, so an unanswered one is "could not
     * check", not an unconfirmed write.
     */
    private function editable(string $number, DisbursementSource $disbursements): Disbursement|RedirectResponse
    {
        DisbursementController::assertUsableNumber($number);

        $disbursement = $disbursements->findDisbursement($number);

        abort_if($disbursement === null, 404);

        try {
            $disbursements->validateUpdate($number);
        } catch (EsbNotFoundException $exception) {
            throw $exception;
        } catch (EsbException $exception) {
            $this->toast('warning', $exception->mayHaveBeenApplied()
                ? sprintf('The ERP did not answer whether disbursement %s can be edited. Try again in a moment. (reference: %s)', $number, $exception->context->correlationId)
                : trim(sprintf(
                    'Disbursement %s cannot be edited: the ERP refused. %s %s',
                    $number,
                    implode(' ', array_map(static fn ($error): string => $error->message, array_slice($exception->fieldErrors, 0, 3))),
                    $exception->safeMessage(),
                )));

            return redirect()->route('disbursements.show', $number);
        }

        return $disbursement;
    }
}
