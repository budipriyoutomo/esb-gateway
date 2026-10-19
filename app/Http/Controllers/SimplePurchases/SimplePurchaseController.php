<?php

namespace App\Http\Controllers\SimplePurchases;

use App\Actions\SimplePurchases\DeleteSimplePurchase;
use App\Http\Controllers\Concerns\HandlesDocumentWrites;
use App\Http\Controllers\Controller;
use App\Http\Requests\SimplePurchases\ListSimplePurchasesRequest;
use App\Integrations\Contracts\SimplePurchaseSource;
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
    use HandlesDocumentWrites;

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
        return $this->deleteOutcome('Simple purchase', 'simple-purchases', $number, fn () => $delete->handle($number));
    }
}
