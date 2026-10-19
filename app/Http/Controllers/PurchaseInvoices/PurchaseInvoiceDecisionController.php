<?php

namespace App\Http\Controllers\PurchaseInvoices;

use App\Actions\PurchaseInvoices\DecidePurchaseInvoice;
use App\Http\Controllers\Concerns\HandlesDocumentWrites;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseInvoices\DecidePurchaseInvoiceRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Authorize or reject a purchase invoice.
 *
 * Every outcome lands back on the detail page, which re-reads the document
 * from the ESB -- the reconciliation step after an unconfirmed write.
 */
class PurchaseInvoiceDecisionController extends Controller
{
    use HandlesDocumentWrites;

    public function store(
        string $number,
        DecidePurchaseInvoiceRequest $request,
        DecidePurchaseInvoice $decide,
    ): RedirectResponse {
        $decision = $request->decision();

        return $this->decisionOutcome(
            'Purchase invoice', 'purchase-invoices', $number, $decision,
            fn () => $decide->handle($number, $decision),
        );
    }
}
