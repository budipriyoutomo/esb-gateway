<?php

namespace App\Http\Controllers\PurchaseOrders;

use App\Actions\PurchaseOrders\DecidePurchaseOrder;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Domain\Canonical\PurchaseOrderDecision;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrders\DecidePurchaseOrderRequest;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbNotFoundException;
use Illuminate\Http\RedirectResponse;

/**
 * Authorize or reject a purchase order.
 *
 * Every outcome lands back on the detail page, which re-reads the document
 * from the ESB -- the reconciliation step after an unconfirmed write.
 */
class PurchaseOrderDecisionController extends Controller
{
    public function store(
        string $number,
        DecidePurchaseOrderRequest $request,
        DecidePurchaseOrder $decide,
    ): RedirectResponse {
        $decision = $request->decision();
        $verb = $decision === PurchaseOrderDecision::Authorize ? 'authorize' : 'reject';

        try {
            $decide->handle($number, $decision, $request->reason());

            $this->toast('success', sprintf(
                'Purchase order %s was %s.',
                $number,
                $decision === PurchaseOrderDecision::Authorize ? 'authorized' : 'rejected',
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

        return redirect()->route('purchase-orders.show', $number);
    }
}
