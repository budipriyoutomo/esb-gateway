<?php

namespace App\Http\Controllers\Purposes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purposes\ListPurposesRequest;
use App\Integrations\Contracts\PurposeSource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Purpose list and detail, read-only ([ESB-015]).
 *
 * Depends on the contract, not on the ESB adapter (ADR-016). The id reaching
 * show() has already been constrained by the route. A not-found refusal from
 * the ESB is rendered as a 404 by EsbExceptionRenderer.
 */
class PurposeController extends Controller
{
    public function index(ListPurposesRequest $request, PurposeSource $purposes): Response
    {
        $page = $purposes->listPurposes($request->toQuery());

        return Inertia::render('purposes/index', [
            'purposes' => $page->purposes,
            'pagination' => $page->pagination,
            'filters' => $request->validatedFilters(),
        ]);
    }

    public function show(int $purposeId, PurposeSource $purposes): Response
    {
        $purpose = $purposes->findPurpose($purposeId);

        abort_if($purpose === null, 404);

        return Inertia::render('purposes/show', [
            'purpose' => $purpose,
        ]);
    }
}
