<?php

namespace App\Http\Controllers\Pricelists;

use App\Actions\Pricelists\PricelistWrites;
use App\Http\Controllers\Concerns\HandlesRecordForms;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pricelists\SavePricelistRequest;
use App\Integrations\Contracts\PricelistSource;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Propose a pricelist, or edit a proposed (temporary) one ([ESB-031], S3).
 * A proposal lands on the list of proposals: the ERP returns no number,
 * and authorizing copies a proposal into the active prices. Authorization
 * (esb.invoke + pricelists.manage) is on the routes and the form request.
 */
class PricelistFormController extends Controller
{
    use HandlesRecordForms;

    private const FIELD_MAP = [
        'supplierID' => 'supplierId',
        'currencyID' => 'currencyId',
        'priceDate' => 'priceDate',
        'expireDate' => 'expireDate',
        'branchIDs' => 'branchIds',
        'pricelistDetails' => 'lines',
    ];

    public function create(): Response
    {
        return Inertia::render('pricelists/form', ['pricelist' => null]);
    }

    public function edit(string $pricelistNum, PricelistSource $pricelists): Response
    {
        abort_unless(preg_match(PricelistController::TEMP_NUMBER, $pricelistNum) === 1, 404);

        $pricelist = $pricelists->findTempPricelist($pricelistNum);

        abort_if($pricelist === null, 404);

        return Inertia::render('pricelists/form', ['pricelist' => $pricelist]);
    }

    public function store(SavePricelistRequest $request, PricelistWrites $writes): RedirectResponse
    {
        $draft = $request->toDraft();
        $name = $request->displayName();

        return $this->recordSaveOutcome('Pricelist', $name, 'pricelists.temp', null, fn () => $writes->create($draft, $name), self::FIELD_MAP);
    }

    public function update(string $pricelistNum, SavePricelistRequest $request, PricelistWrites $writes): RedirectResponse
    {
        abort_unless(preg_match(PricelistController::TEMP_NUMBER, $pricelistNum) === 1, 404);

        $draft = $request->toDraft();

        return $this->recordSaveOutcome(
            'Pricelist',
            $pricelistNum,
            'pricelists.temp',
            $pricelistNum,
            fn () => $writes->update($pricelistNum, $draft),
            self::FIELD_MAP,
        );
    }
}
