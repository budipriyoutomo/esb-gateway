<?php

namespace App\Http\Controllers\ProductSubCategories;

use App\Actions\ProductSubCategories\ProductSubCategoryWrites;
use App\Http\Controllers\Concerns\HandlesRecordForms;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductSubCategories\SaveProductSubCategoryRequest;
use App\Integrations\Contracts\ProductSubCategorySource;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Create and edit product-sub-categories ([ESB-030], S3). Authorization
 * (esb.invoke + the module's manage permission) is on the routes and the
 * form request; the outcomes are HandlesRecordForms'.
 */
class ProductSubCategoryFormController extends Controller
{
    use HandlesRecordForms;

    /**
     * The vendor's request field names, and the form fields they belong to.
     */
    private const FIELD_MAP = [
        'subCategoryName' => 'name',
        'deadStockThreshold' => 'deadStockDays',
        'notes' => 'notes',
    ];

    public function create(): Response
    {
        return Inertia::render('product-sub-categories/form', ['subCategory' => null]);
    }

    public function edit(int $subCategoryId, ProductSubCategorySource $source): Response
    {
        $record = $source->findSubCategory($subCategoryId);

        abort_if($record === null, 404);

        return Inertia::render('product-sub-categories/form', ['subCategory' => $record]);
    }

    public function store(SaveProductSubCategoryRequest $request, ProductSubCategoryWrites $writes): RedirectResponse
    {
        $draft = $request->toDraft();

        return $this->recordSaveOutcome('Sub-category', $draft->name, 'product-sub-categories', null, fn () => $writes->create($draft), self::FIELD_MAP);
    }

    public function update(int $subCategoryId, SaveProductSubCategoryRequest $request, ProductSubCategoryWrites $writes): RedirectResponse
    {
        $draft = $request->toDraft();

        return $this->recordSaveOutcome(
            'Sub-category',
            $draft->name,
            'product-sub-categories',
            $subCategoryId,
            fn () => $writes->update($subCategoryId, $draft),
            self::FIELD_MAP,
        );
    }
}
