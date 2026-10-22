<?php

namespace App\Http\Controllers\MenuCategories;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuCategories\ListMenuCategoriesRequest;
use App\Integrations\Contracts\MenuCategorySource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * POS menu categories ([ESB-047], S1). Authorization (esb.invoke) is on
 * the form request. The vendor has no detail operation: a category's page
 * reads the list page it was listed on first.
 */
class MenuCategoryController extends Controller
{
    public function index(ListMenuCategoriesRequest $request, MenuCategorySource $categories): Response
    {
        $page = $categories->listCategories($request->page());

        return Inertia::render('menu-categories/index', [
            'categories' => $page->categories,
            'pagination' => $page->pagination,
            'filters' => ['page' => $request->page()],
        ]);
    }

    public function show(int $id, ListMenuCategoriesRequest $request, MenuCategorySource $categories): Response
    {
        $found = $categories->findCategory($id, $request->page());

        abort_if($found === null, 404);

        [$category, $page] = $found;

        return Inertia::render('menu-categories/show', [
            'category' => $category,
            'page' => $page,
        ]);
    }
}
