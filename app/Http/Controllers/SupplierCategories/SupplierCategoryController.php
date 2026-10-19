<?php

namespace App\Http\Controllers\SupplierCategories;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierCategories\ListSupplierCategoriesRequest;
use App\Integrations\Contracts\SupplierCategorySource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Supplier category list and detail, read-only ([ESB-023]). A not-found
 * refusal from the ESB is rendered as a 404 by EsbExceptionRenderer.
 */
class SupplierCategoryController extends Controller
{
    public function index(ListSupplierCategoriesRequest $request, SupplierCategorySource $categories): Response
    {
        $page = $categories->listCategories($request->toQuery());

        return Inertia::render('supplier-categories/index', [
            'categories' => $page->categories,
            'pagination' => $page->pagination,
            'filters' => $request->validatedFilters(),
        ]);
    }

    public function show(int $supplierCategoryId, SupplierCategorySource $categories): Response
    {
        $category = $categories->findCategory($supplierCategoryId);

        abort_if($category === null, 404);

        return Inertia::render('supplier-categories/show', [
            'category' => $category,
        ]);
    }
}
