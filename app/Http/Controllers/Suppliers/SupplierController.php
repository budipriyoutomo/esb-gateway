<?php

namespace App\Http\Controllers\Suppliers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Suppliers\ListSuppliersRequest;
use App\Integrations\Contracts\SupplierSource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Supplier list and detail, read-only ([ESB-024]). A not-found refusal from
 * the ESB is rendered as a 404 by EsbExceptionRenderer.
 */
class SupplierController extends Controller
{
    public function index(ListSuppliersRequest $request, SupplierSource $suppliers): Response
    {
        $page = $suppliers->listSuppliers($request->toQuery());

        return Inertia::render('suppliers/index', [
            'suppliers' => $page->suppliers,
            'pagination' => $page->pagination,
            'filters' => $request->validatedFilters(),
        ]);
    }

    public function show(int $supplierId, SupplierSource $suppliers): Response
    {
        $supplier = $suppliers->findSupplier($supplierId);

        abort_if($supplier === null, 404);

        return Inertia::render('suppliers/show', [
            'supplier' => $supplier,
        ]);
    }
}
