<?php

namespace App\Http\Controllers\Products;

use App\Actions\Products\ProductWrites;
use App\Http\Controllers\Concerns\HandlesRecordForms;
use App\Http\Controllers\Controller;
use App\Http\Requests\Products\SaveProductRequest;
use App\Integrations\Contracts\ProductSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Create and edit products with their units ([ESB-031], S3). An update
 * reads the product first: PUT replaces it, and what the form does not edit
 * is carried over. Authorization (esb.invoke + products.manage) is on the
 * routes and the form request.
 */
class ProductFormController extends Controller
{
    use HandlesRecordForms;

    private const FIELD_MAP = [
        'productName' => 'name',
        'productCode' => 'code',
        'categoryID' => 'categoryId',
        'subCategoryID' => 'subCategoryId',
        'notes' => 'notes',
        'receiptTolerance' => 'receiptTolerance',
        'productDetails' => 'units',
    ];

    public function create(): Response
    {
        return Inertia::render('products/form', ['product' => null]);
    }

    public function edit(int $productId, ProductSource $products): Response
    {
        $product = $products->findProduct($productId);

        abort_if($product === null, 404);

        return Inertia::render('products/form', ['product' => $product]);
    }

    public function store(SaveProductRequest $request, ProductWrites $writes): RedirectResponse
    {
        $draft = $request->toDraft();

        return $this->recordSaveOutcome('Product', $draft->name, 'products', null, fn () => $writes->create($draft), self::FIELD_MAP);
    }

    public function update(int $productId, SaveProductRequest $request, ProductSource $products, ProductWrites $writes): RedirectResponse
    {
        $draft = $request->toDraft();

        // A read, before the guarded write: its failure is never mistaken
        // for an unconfirmed update.
        $current = $products->findProduct($productId);

        abort_if($current === null, 404);

        // A unit id must be one of this product's own.
        $own = array_map(static fn ($unit): int => $unit->productDetailId, $current->units);

        foreach ($draft->units as $index => $unit) {
            if ($unit->productDetailId !== null && ! in_array($unit->productDetailId, $own, true)) {
                throw ValidationException::withMessages([
                    "units.{$index}.productDetailId" => 'This unit does not belong to the product.',
                ]);
            }
        }

        return $this->recordSaveOutcome(
            'Product',
            $draft->name,
            'products',
            $productId,
            fn () => $writes->update($productId, $draft, $current),
            self::FIELD_MAP,
        );
    }
}
