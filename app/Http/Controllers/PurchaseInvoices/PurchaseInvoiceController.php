<?php

namespace App\Http\Controllers\PurchaseInvoices;

use App\Actions\PurchaseInvoices\DeletePurchaseInvoice;
use App\Http\Controllers\Concerns\HandlesDocumentWrites;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseInvoices\ListPurchaseInvoicesRequest;
use App\Integrations\Contracts\PurchaseInvoiceSource;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Purchase invoice list, detail, and delete.
 *
 * Depends on the contract, not on the ESB adapter (ADR-016). The document
 * number reaching show() and destroy() has already been constrained by the
 * route.
 */
class PurchaseInvoiceController extends Controller
{
    use HandlesDocumentWrites;

    public function index(ListPurchaseInvoicesRequest $request, PurchaseInvoiceSource $purchaseInvoices): Response
    {
        $page = $purchaseInvoices->listPurchaseInvoices($request->toQuery());

        return Inertia::render('purchase-invoices/index', [
            'purchaseInvoices' => $page->purchaseInvoices,
            'pagination' => $page->pagination,
            'filters' => $request->validatedFilters(),
        ]);
    }

    public function show(string $number, PurchaseInvoiceSource $purchaseInvoices): Response
    {
        return Inertia::render('purchase-invoices/show', [
            'purchaseInvoice' => $purchaseInvoices->findPurchaseInvoice($number),
        ]);
    }

    /**
     * A definite refusal returns to the document; an unconfirmed delete lands
     * on the list, where the user can see whether it is gone.
     */
    public function destroy(string $number, DeletePurchaseInvoice $delete): RedirectResponse
    {
        return $this->deleteOutcome('Purchase invoice', 'purchase-invoices', $number, fn () => $delete->handle($number));
    }
}
