<?php

namespace App\Http\Controllers\PurchaseRequests;

use App\Actions\PurchaseRequests\DecidePurchaseRequest;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Domain\Canonical\PurchaseRequestDecision;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequests\DecidePurchaseRequestRequest;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbNotFoundException;
use Illuminate\Http\RedirectResponse;

/**
 * Authorize or reject a purchase request.
 *
 * Every outcome lands back on the detail page, which re-reads the document
 * from the ESB. After an unconfirmed write that read is the reconciliation
 * step docs/05-api-integration.md asks for: the user sees the actual state
 * before deciding whether to try again.
 */
class PurchaseRequestDecisionController extends Controller
{
    public function store(
        string $number,
        DecidePurchaseRequestRequest $request,
        DecidePurchaseRequest $decide,
    ): RedirectResponse {
        $decision = $request->decision();
        $verb = $decision === PurchaseRequestDecision::Authorize ? 'authorize' : 'reject';

        try {
            $decide->handle($number, $decision);

            $this->toast('success', sprintf(
                'Purchase request %s was %s.',
                $number,
                $decision === PurchaseRequestDecision::Authorize ? 'authorized' : 'rejected',
            ));
        } catch (WriteAlreadyInFlight) {
            $this->toast('warning', sprintf(
                'Another change to %s is still being processed. Check its status before trying again.',
                $number,
            ));
        } catch (EsbNotFoundException $exception) {
            throw $exception;
        } catch (EsbException $exception) {
            if ($exception->mayHaveBeenApplied()) {
                $this->toast('warning', sprintf(
                    'The ERP did not confirm the request to %s %s. It may or may not have been applied -- check the status below before trying again. (reference: %s)',
                    $verb,
                    $number,
                    $exception->context->correlationId,
                ));
            } else {
                $this->toast('error', sprintf('Could not %s %s. %s', $verb, $number, $exception->safeMessage()));
            }
        }

        return redirect()->route('purchase-requests.show', $number);
    }
}
