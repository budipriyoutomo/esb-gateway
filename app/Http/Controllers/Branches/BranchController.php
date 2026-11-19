<?php

namespace App\Http\Controllers\Branches;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branches\ListBranchesRequest;
use App\Integrations\Contracts\BranchSource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Branch list and detail, read-only ([ESB-051]).
 *
 * Depends on the contract, not on the ESB adapter (ADR-016). The id reaching
 * show() has already been constrained by the route. A not-found refusal from
 * the ESB is rendered as a 404 by EsbExceptionRenderer.
 */
class BranchController extends Controller
{
    public function index(ListBranchesRequest $request, BranchSource $branches): Response
    {
        $page = $branches->listBranches($request->toQuery());

        return Inertia::render('branches/index', [
            'branches' => $page->branches,
            'pagination' => $page->pagination,
            'filters' => $request->validatedFilters(),
        ]);
    }

    public function show(int $branchId, BranchSource $branches): Response
    {
        $branch = $branches->findBranch($branchId);

        abort_if($branch === null, 404);

        return Inertia::render('branches/show', [
            'branch' => $branch,
        ]);
    }
}
