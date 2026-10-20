<?php

namespace App\Http\Controllers\SupplierCategories;

use App\Actions\SupplierCategories\SupplierCategoryWrites;
use App\Http\Controllers\Concerns\HandlesRecordForms;
use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierCategories\SaveSupplierCategoryRequest;
use App\Integrations\Contracts\SupplierCategorySource;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Create and edit supplier-categories ([ESB-030], S3). Authorization
 * (esb.invoke + the module's manage permission) is on the routes and the
 * form request; the outcomes are HandlesRecordForms'.
 */
class SupplierCategoryFormController extends Controller
{
    use HandlesRecordForms;

    /**
     * The vendor's request field names, and the form fields they belong to.
     */
    private const FIELD_MAP = [
        'supplierCategoryName' => 'name',
    ];

    public function create(): Response
    {
        return Inertia::render('supplier-categories/form', ['category' => null]);
    }

    public function edit(int $supplierCategoryId, SupplierCategorySource $source): Response
    {
        $record = $source->findCategory($supplierCategoryId);

        abort_if($record === null, 404);

        return Inertia::render('supplier-categories/form', ['category' => $record]);
    }

    public function store(SaveSupplierCategoryRequest $request, SupplierCategoryWrites $writes): RedirectResponse
    {
        $draft = $request->toDraft();

        return $this->recordSaveOutcome('Supplier category', $draft->name, 'supplier-categories', null, fn () => $writes->create($draft), self::FIELD_MAP);
    }

    public function update(int $supplierCategoryId, SaveSupplierCategoryRequest $request, SupplierCategoryWrites $writes): RedirectResponse
    {
        $draft = $request->toDraft();

        return $this->recordSaveOutcome(
            'Supplier category',
            $draft->name,
            'supplier-categories',
            $supplierCategoryId,
            fn () => $writes->update($supplierCategoryId, $draft),
            self::FIELD_MAP,
        );
    }
}
