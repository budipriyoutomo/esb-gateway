<?php

namespace App\Http\Controllers\PurchaseRequests;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseRequests\ListPurchaseRequestsRequest;
use App\Integrations\Contracts\PurchaseRequestSource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only purchase request screens.
 *
 * Depends on the contract, not on the ESB adapter (ADR-016). The document
 * number reaching show() has already been constrained by the route, and access
 * to both actions is esb.invoke, since each one sends an operation to the ESB.
 */
class PurchaseRequestController extends Controller
{
    public function index(ListPurchaseRequestsRequest $request, PurchaseRequestSource $purchaseRequests): Response
    {
        $page = $purchaseRequests->listPurchaseRequests($request->toQuery());

        return Inertia::render('purchase-requests/index', [
            'purchaseRequests' => $page->purchaseRequests,
            'pagination' => $page->pagination,
            'filters' => $request->validatedFilters(),
        ]);
    }

    public function show(string $number, PurchaseRequestSource $purchaseRequests): Response
    {
        return Inertia::render('purchase-requests/show', [
            'purchaseRequest' => $purchaseRequests->findPurchaseRequest($number),
        ]);
    }
}
