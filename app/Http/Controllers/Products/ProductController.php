<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\ListProductsRequest;
use App\Integrations\ESB\Clients\ProductClient;
use App\Integrations\ESB\DTOs\ProductListQuery;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(ListProductsRequest $request, ProductClient $products): Response
    {
        $result = $products->list($request->toQuery());

        return Inertia::render('products/index', [
            'products' => $result->products,
            'pagination' => $result->pagination,
            'filters' => $request->toQuery()->toQueryParameters(),
            'sortable' => ProductListQuery::SORTABLE,
        ]);
    }
}
