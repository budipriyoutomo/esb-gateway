<?php

namespace App\Http\Controllers\Pricelists;

use App\Actions\Pricelists\DecideTempPricelist;
use App\Actions\Pricelists\DeleteTempPricelist;
use App\Http\Controllers\Concerns\HandlesDocumentWrites;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pricelists\DecideTempPricelistRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Authorize, reject or delete a temporary pricelist ([ESB-027], S2). The
 * number must contain a digit (/pricelist/temp/export is a vendor path).
 * Authorizing removes the proposal, so it lands on the list.
 */
class TempPricelistWriteController extends Controller
{
    use HandlesDocumentWrites;

    public function decide(string $pricelistNum, DecideTempPricelistRequest $request, DecideTempPricelist $decide): RedirectResponse
    {
        abort_unless(preg_match(PricelistController::TEMP_NUMBER, $pricelistNum) === 1, 404);

        $decision = $request->decision();

        return $this->decisionOutcome(
            'Proposed pricelist', 'pricelists.temp', $pricelistNum, $decision,
            fn () => $decide->handle($pricelistNum, $decision),
            authorizeConsumesDocument: true,
        );
    }

    public function destroy(string $pricelistNum, DeleteTempPricelist $delete): RedirectResponse
    {
        abort_unless(preg_match(PricelistController::TEMP_NUMBER, $pricelistNum) === 1, 404);

        return $this->deleteOutcome('Proposed pricelist', 'pricelists.temp', $pricelistNum, fn () => $delete->handle($pricelistNum));
    }
}
