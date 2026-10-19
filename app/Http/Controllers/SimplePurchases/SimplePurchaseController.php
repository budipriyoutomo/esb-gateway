<?php

namespace App\Http\Controllers\SimplePurchases;

use App\Actions\SimplePurchases\DeleteSimplePurchase;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Http\Controllers\Controller;
use App\Http\Requests\SimplePurchases\ListSimplePurchasesRequest;
use App\Integrations\Contracts\SimplePurchaseSource;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbNotFoundException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Simple purchase list, detail, and delete.
 *
 * Depends on the contract, not on the ESB adapter (ADR-016). The document
 * number reaching show() and destroy() has already been constrained by the
 * route.
 */
class SimplePurchaseController extends Controller
{
    public function index(ListSimplePurchasesRequest $request, SimplePurchaseSource $simplePurchases): Response
    {
        $page = $simplePurchases->listSimplePurchases($request->toQuery());

        return Inertia::render('simple-purchases/index', [
            'simplePurchases' => $page->simplePurchases,
            'pagination' => $page->pagination,
            'filters' => $request->validatedFilters(),
        ]);
    }

    public function show(string $number, SimplePurchaseSource $simplePurchases): Response
    {
        return Inertia::render('simple-purchases/show', [
            'simplePurchase' => $simplePurchases->findSimplePurchase($number),
        ]);
    }

    /**
     * A definite refusal returns to the document; an unconfirmed delete lands
     * on the list, where the user can see whether it is gone.
     */
    public function destroy(string $number, DeleteSimplePurchase $delete): RedirectResponse
    {
        try {
            $delete->handle($number);
        } catch (WriteAlreadyInFlight) {
            $this->toast('warning', sprintf('Another change to %s is still being processed. Check it before trying again.', $number));

            return redirect()->route('simple-purchases.show', $number);
        } catch (EsbNotFoundException $exception) {
            throw $exception;
        } catch (EsbException $exception) {
            if ($exception->mayHaveBeenApplied()) {
                $this->toast('warning', sprintf(
                    'The ERP did not confirm deleting %s. It may or may not be gone -- check the list before trying again. (reference: %s)',
                    $number,
                    $exception->context->correlationId,
                ));

                return redirect()->route('simple-purchases.index');
            }

            $this->toast('error', sprintf('Could not delete %s. %s', $number, $exception->safeMessage()));

            return redirect()->route('simple-purchases.show', $number);
        }

        $this->toast('success', sprintf('Simple purchase %s was deleted.', $number));

        return redirect()->route('simple-purchases.index');
    }
}
