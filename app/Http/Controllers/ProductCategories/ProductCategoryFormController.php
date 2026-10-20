<?php

namespace App\Http\Controllers\ProductCategories;

use App\Actions\ProductCategories\ProductCategoryWrites;
use App\Http\Controllers\Concerns\HandlesRecordForms;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductCategories\SaveProductCategoryRequest;
use App\Integrations\Contracts\ProductCategorySource;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Create and edit product-categories ([ESB-030], S3). Authorization
 * (esb.invoke + the module's manage permission) is on the routes and the
 * form request; the outcomes are HandlesRecordForms'.
 */
class ProductCategoryFormController extends Controller
{
    use HandlesRecordForms;

    /**
     * The vendor's request field names, and the form fields they belong to.
     */
    private const FIELD_MAP = [
        'categoryName' => 'name',
        'categoryCode' => 'code',
        'categoryTypeID' => 'typeId',
        'notes' => 'notes',
        'coaNo' => 'coaNo',
        'cogsCoaNo' => 'cogsCoaNo',
        'accrualBasisCoaNo' => 'accrualBasisCoaNo',
        'opnameCoaNo' => 'opnameCoaNo',
        'productSalesCoaNo' => 'productSalesCoaNo',
        'depCoaNo' => 'depreciationCoaNo',
        'depExpenseCoaNo' => 'depreciationExpenseCoaNo',
        'depLength' => 'depreciationMonths',
        'depLengthFiscal' => 'fiscalDepreciationMonths',
        'depreciationTypeID' => 'depreciationTypeId',
        'depreciationTypeIDFiscal' => 'fiscalDepreciationTypeId',
        'flagAssetTax' => 'assetTax',
    ];

    public function create(): Response
    {
        return Inertia::render('product-categories/form', ['category' => null]);
    }

    public function edit(int $categoryId, ProductCategorySource $source): Response
    {
        $record = $source->findCategory($categoryId);

        abort_if($record === null, 404);

        return Inertia::render('product-categories/form', ['category' => $record]);
    }

    public function store(SaveProductCategoryRequest $request, ProductCategoryWrites $writes): RedirectResponse
    {
        $draft = $request->toDraft();

        return $this->recordSaveOutcome('Category', $draft->name, 'product-categories', null, fn () => $writes->create($draft), self::FIELD_MAP);
    }

    public function update(int $categoryId, SaveProductCategoryRequest $request, ProductCategoryWrites $writes): RedirectResponse
    {
        $draft = $request->toDraft();

        return $this->recordSaveOutcome(
            'Category',
            $draft->name,
            'product-categories',
            $categoryId,
            fn () => $writes->update($categoryId, $draft),
            self::FIELD_MAP,
        );
    }
}
