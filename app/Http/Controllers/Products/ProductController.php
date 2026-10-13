<?php

namespace App\Http\Controllers\Products;

use App\Domain\Canonical\ProductQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Products\ListProductsRequest;
use App\Integrations\Contracts\ProductSource;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Depends on the contract, not on the ESB adapter (ADR-016): which provider
     * answers is a binding, resolved in AppServiceProvider.
     */
    public function index(ListProductsRequest $request, ProductSource $products): Response
    {
        $query = $request->toQuery();

        $page = $products->listProducts($query);

        return Inertia::render('products/index', [
            'products' => $page->products,
            'pagination' => $page->pagination,
            'filters' => $request->validatedFilters(),
            'sortable' => ProductQuery::SORTABLE,
        ]);
    }
}
