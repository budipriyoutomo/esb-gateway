<?php

namespace App\Http\Controllers\BillsOfMaterial;

use App\Http\Controllers\Controller;
use App\Http\Requests\BillsOfMaterial\ListBillsOfMaterialRequest;
use App\Integrations\Contracts\BillOfMaterialSource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Bill of material list and detail, read-only ([ESB-014]).
 *
 * Depends on the contract, not on the ESB adapter (ADR-016). The id reaching
 * show() has already been constrained by the route. A not-found refusal from
 * the ESB is rendered as a 404 by EsbExceptionRenderer.
 */
class BillOfMaterialController extends Controller
{
    public function index(ListBillsOfMaterialRequest $request, BillOfMaterialSource $boms): Response
    {
        $page = $boms->listBillsOfMaterial($request->toQuery());

        return Inertia::render('bills-of-material/index', [
            'boms' => $page->boms,
            'pagination' => $page->pagination,
            'filters' => $request->validatedFilters(),
        ]);
    }

    public function show(int $bomId, BillOfMaterialSource $boms): Response
    {
        $bom = $boms->findBillOfMaterial($bomId);

        abort_if($bom === null, 404);

        return Inertia::render('bills-of-material/show', [
            'bom' => $bom,
        ]);
    }
}
