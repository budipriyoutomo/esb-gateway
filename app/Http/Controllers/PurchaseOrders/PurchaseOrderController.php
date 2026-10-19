<?php

namespace App\Http\Controllers\PurchaseOrders;

use App\Actions\PurchaseOrders\DeletePurchaseOrder;
use App\Http\Controllers\Concerns\HandlesDocumentWrites;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrders\ListPurchaseOrdersRequest;
use App\Integrations\Contracts\PurchaseOrderSource;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Purchase order list, detail, and delete.
 *
 * Depends on the contract, not on the ESB adapter (ADR-016). The document
 * number reaching show() and destroy() has already been constrained by the
 * route.
 */
class PurchaseOrderController extends Controller
{
    use HandlesDocumentWrites;

    public function index(ListPurchaseOrdersRequest $request, PurchaseOrderSource $purchaseOrders): Response
    {
        $page = $purchaseOrders->listPurchaseOrders($request->toQuery());

        return Inertia::render('purchase-orders/index', [
            'purchaseOrders' => $page->purchaseOrders,
            'pagination' => $page->pagination,
            'filters' => $request->validatedFilters(),
        ]);
    }

    public function show(string $number, PurchaseOrderSource $purchaseOrders): Response
    {
        return Inertia::render('purchase-orders/show', [
            'purchaseOrder' => $purchaseOrders->findPurchaseOrder($number),
        ]);
    }

    /**
     * A definite refusal returns to the document; an unconfirmed delete lands
     * on the list, where the user can see whether it is gone.
     */
    public function destroy(string $number, DeletePurchaseOrder $delete): RedirectResponse
    {
        return $this->deleteOutcome('Purchase order', 'purchase-orders', $number, fn () => $delete->handle($number));
    }
}
