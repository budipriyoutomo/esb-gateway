<?php

namespace App\Http\Controllers\SimplePurchases;

use App\Actions\SimplePurchases\DecideSimplePurchase;
use App\Http\Controllers\Concerns\HandlesDocumentWrites;
use App\Http\Controllers\Controller;
use App\Http\Requests\SimplePurchases\DecideSimplePurchaseRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Authorize or reject a simple purchase.
 *
 * Every outcome lands back on the detail page, which re-reads the document
 * from the ESB -- the reconciliation step after an unconfirmed write.
 */
class SimplePurchaseDecisionController extends Controller
{
    use HandlesDocumentWrites;

    public function store(
        string $number,
        DecideSimplePurchaseRequest $request,
        DecideSimplePurchase $decide,
    ): RedirectResponse {
        $decision = $request->decision();

        return $this->decisionOutcome(
            'Simple purchase', 'simple-purchases', $number, $decision,
            fn () => $decide->handle($number, $decision, $request->reason()),
        );
    }
}
