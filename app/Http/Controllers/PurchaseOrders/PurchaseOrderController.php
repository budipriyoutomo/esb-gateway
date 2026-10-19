<?php

namespace App\Http\Controllers\PurchaseOrders;

use App\Actions\PurchaseOrders\DeletePurchaseOrder;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrders\ListPurchaseOrdersRequest;
use App\Integrations\Contracts\PurchaseOrderSource;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbNotFoundException;
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
        try {
            $delete->handle($number);
        } catch (WriteAlreadyInFlight) {
            $this->toast('warning', sprintf('Another change to %s is still being processed. Check it before trying again.', $number));

            return redirect()->route('purchase-orders.show', $number);
        } catch (EsbNotFoundException $exception) {
            throw $exception;
        } catch (EsbException $exception) {
            if ($exception->mayHaveBeenApplied()) {
                $this->toast('warning', sprintf(
                    'The ERP did not confirm deleting %s. It may or may not be gone -- check the list before trying again. (reference: %s)',
                    $number,
                    $exception->context->correlationId,
                ));

                return redirect()->route('purchase-orders.index');
            }

            $this->toast('error', sprintf('Could not delete %s. %s', $number, $exception->safeMessage()));

            return redirect()->route('purchase-orders.show', $number);
        }

        $this->toast('success', sprintf('Purchase order %s was deleted.', $number));

        return redirect()->route('purchase-orders.index');
    }
}
