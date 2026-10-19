<?php

namespace App\Http\Controllers\ProductCategories;

use App\Domain\Canonical\ProductCategoryQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductCategories\ListProductCategoriesRequest;
use App\Integrations\Contracts\ProductCategorySource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Product category list and detail, read-only ([ESB-021]). A not-found
 * refusal from the ESB is rendered as a 404 by EsbExceptionRenderer.
 */
class ProductCategoryController extends Controller
{
    public function index(ListProductCategoriesRequest $request, ProductCategorySource $categories): Response
    {
        $page = $categories->listCategories($request->toQuery());

        return Inertia::render('product-categories/index', [
            'categories' => $page->categories,
            'pagination' => $page->pagination,
            'filters' => $request->validatedFilters(),
            'categoryTypes' => ProductCategoryQuery::TYPES,
        ]);
    }

    public function show(int $categoryId, ProductCategorySource $categories): Response
    {
        $category = $categories->findCategory($categoryId);

        abort_if($category === null, 404);

        return Inertia::render('product-categories/show', [
            'category' => $category,
        ]);
    }
}
