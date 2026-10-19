<?php

namespace App\Http\Controllers\Units;

use App\Http\Controllers\Controller;
use App\Http\Requests\Units\ListUnitsRequest;
use App\Integrations\Contracts\UnitOfMeasureSource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Unit of measure list and detail, read-only ([ESB-020]). A not-found
 * refusal from the ESB is rendered as a 404 by EsbExceptionRenderer.
 */
class UnitOfMeasureController extends Controller
{
    public function index(ListUnitsRequest $request, UnitOfMeasureSource $units): Response
    {
        $page = $units->listUnits($request->toQuery());

        return Inertia::render('units/index', [
            'units' => $page->units,
            'pagination' => $page->pagination,
            'filters' => $request->validatedFilters(),
        ]);
    }

    public function show(int $uomId, UnitOfMeasureSource $units): Response
    {
        $unit = $units->findUnit($uomId);

        abort_if($unit === null, 404);

        return Inertia::render('units/show', [
            'unit' => $unit,
        ]);
    }
}
