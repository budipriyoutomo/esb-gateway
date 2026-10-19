<?php

namespace App\Http\Controllers\PurchaseOrders;

use App\Actions\PurchaseOrders\DecidePurchaseOrder;
use App\Http\Controllers\Concerns\HandlesDocumentWrites;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrders\DecidePurchaseOrderRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Authorize or reject a purchase order.
 *
 * Every outcome lands back on the detail page, which re-reads the document
 * from the ESB -- the reconciliation step after an unconfirmed write.
 */
class PurchaseOrderDecisionController extends Controller
{
    use HandlesDocumentWrites;

    public function store(
        string $number,
        DecidePurchaseOrderRequest $request,
        DecidePurchaseOrder $decide,
    ): RedirectResponse {
        $decision = $request->decision();

        return $this->decisionOutcome(
            'Purchase order', 'purchase-orders', $number, $decision,
            fn () => $decide->handle($number, $decision, $request->reason()),
        );
    }
}
