<?php

namespace App\Http\Controllers\ProductSubCategories;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductSubCategories\ListProductSubCategoriesRequest;
use App\Integrations\Contracts\ProductSubCategorySource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Product sub-category list and detail, read-only ([ESB-022]). A not-found
 * refusal from the ESB is rendered as a 404 by EsbExceptionRenderer.
 */
class ProductSubCategoryController extends Controller
{
    public function index(ListProductSubCategoriesRequest $request, ProductSubCategorySource $subCategories): Response
    {
        $page = $subCategories->listSubCategories($request->toQuery());

        return Inertia::render('product-sub-categories/index', [
            'subCategories' => $page->subCategories,
            'pagination' => $page->pagination,
            'filters' => $request->validatedFilters(),
        ]);
    }

    public function show(int $subCategoryId, ProductSubCategorySource $subCategories): Response
    {
        $subCategory = $subCategories->findSubCategory($subCategoryId);

        abort_if($subCategory === null, 404);

        return Inertia::render('product-sub-categories/show', [
            'subCategory' => $subCategory,
        ]);
    }
}
