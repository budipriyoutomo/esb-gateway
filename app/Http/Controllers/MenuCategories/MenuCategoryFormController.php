<?php

namespace App\Http\Controllers\MenuCategories;

use App\Actions\MenuCategories\MenuCategoryWrites;
use App\Http\Controllers\Concerns\HandlesRecordForms;
use App\Http\Controllers\Controller;
use App\Http\Requests\MenuCategories\ListMenuCategoriesRequest;
use App\Http\Requests\MenuCategories\SaveMenuCategoryRequest;
use App\Integrations\Contracts\MenuCategorySource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Create and edit POS menu categories ([ESB-048], S3). Authorization
 * (esb.invoke + menu-categories.manage) is on the routes and the form
 * request; the outcomes are HandlesRecordForms'.
 *
 * An update reads the category first, outside the guarded write, and keeps
 * every current detail: what the vendor does with one left out is
 * undocumented.
 */
class MenuCategoryFormController extends Controller
{
    use HandlesRecordForms;

    /**
     * Vendor field => form field, for the ERP's EC0110 errors (ADR-021). The
     * vendor names a detail's name "menuCategoryDetailDesc", with no index.
     */
    private const FIELD_MAP = [
        'menuCategoryName' => 'name',
        'menuCategoryCode' => 'code',
        'salesAccount' => 'salesAccount',
        'cogsAccount' => 'cogsAccount',
        'discountAccount' => 'discountAccount',
        'description' => 'description',
        'themeCategoryOnPos' => 'buttonColor',
        'menuCategoryDetails' => 'details',
        'menuCategoryDetailDesc' => 'details',
    ];

    public function create(): Response
    {
        return Inertia::render('menu-categories/form', ['category' => null, 'page' => null]);
    }

    public function store(SaveMenuCategoryRequest $request, MenuCategoryWrites $writes): RedirectResponse
    {
        $draft = $request->toDraft();

        return $this->recordSaveOutcome('Menu category', $draft->name, 'menu-categories', null, fn (): ?int => $writes->create($draft), self::FIELD_MAP);
    }

    public function edit(int $id, ListMenuCategoriesRequest $request, MenuCategorySource $categories): Response
    {
        $found = $categories->findCategory($id, $request->page());

        abort_if($found === null, 404);

        return Inertia::render('menu-categories/form', ['category' => $found[0], 'page' => $found[1]]);
    }

    public function update(int $id, SaveMenuCategoryRequest $request, MenuCategorySource $categories, MenuCategoryWrites $writes): RedirectResponse
    {
        $draft = $request->toDraft();
        $found = $categories->findCategory($id, $request->hintPage());

        abort_if($found === null, 404);

        $current = $found[0]->detailIds();
        $kept = $draft->detailIds();

        if (array_diff($current, $kept) !== []) {
            throw ValidationException::withMessages([
                'details' => 'Every current category detail must stay: remove a detail in the ERP.',
            ]);
        }

        if (array_diff($kept, $current) !== []) {
            throw ValidationException::withMessages([
                'details' => 'A category detail does not belong to this category. Reload the page and try again.',
            ]);
        }

        return $this->recordSaveOutcome(
            'Menu category',
            $draft->name,
            'menu-categories',
            $id,
            fn () => $writes->update($id, $draft),
            self::FIELD_MAP,
        );
    }
}
