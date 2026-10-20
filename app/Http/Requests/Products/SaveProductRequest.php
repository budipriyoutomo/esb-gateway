<?php

namespace App\Http\Requests\Products;

use App\Authorization\Permission;
use App\Domain\Canonical\ProductDraft;
use App\Domain\Canonical\ProductUnitDraft;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Validator;

/**
 * A product to create, or to replace an existing one with ([ESB-031]).
 *
 * Mirrors the vendor schema of POST /product (PUT takes the same): name
 * required, max 100; code max 50; category and sub-category required; notes
 * max 100; at least one unit, each with a unit of measure, SKU max 50. Each
 * unit of measure once, and exactly one base unit -- assumed, the vendor
 * states neither.
 */
class SaveProductRequest extends BaseFormRequest
{
    /** A bound on units; the vendor documents none. */
    public const MAX_UNITS = 50;

    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, Permission::ManageProducts);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:50'],
            'categoryId' => ['required', 'integer', 'min:1'],
            'subCategoryId' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:100'],
            'purchasable' => ['sometimes', 'boolean'],
            'saleable' => ['sometimes', 'boolean'],
            'requestable' => ['sometimes', 'boolean'],
            'vat' => ['sometimes', 'boolean'],
            'luxury' => ['sometimes', 'boolean'],
            'receiptTolerance' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'units' => ['required', 'array', 'min:1', 'max:'.self::MAX_UNITS],
            'units.*.productDetailId' => ['nullable', 'integer', 'min:1', 'distinct'],
            'units.*.uomId' => ['required', 'integer', 'min:1', 'distinct'],
            'units.*.qty' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'units.*.isBase' => ['sometimes', 'boolean'],
            'units.*.purchasable' => ['sometimes', 'boolean'],
            'units.*.saleable' => ['sometimes', 'boolean'],
            'units.*.stocked' => ['sometimes', 'boolean'],
            'units.*.transferable' => ['sometimes', 'boolean'],
            'units.*.sku' => ['nullable', 'string', 'max:50'],
            'units.*.basePrice' => ['nullable', 'numeric', 'min:0', 'max:9999999999999999'],
            'units.*.weight' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'units.*.active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $units = $this->input('units');

                if (! is_array($units) || $units === []) {
                    return;
                }

                $bases = count(array_filter($units, static fn (mixed $unit): bool => is_array($unit) && filter_var($unit['isBase'] ?? false, FILTER_VALIDATE_BOOLEAN)));

                if ($bases !== 1) {
                    $validator->errors()->add('units', 'Mark exactly one unit as the base unit.');
                }
            },
        ];
    }

    public function toDraft(): ProductDraft
    {
        /** @var list<array<string, mixed>> $units */
        $units = $this->validated('units');

        return new ProductDraft(
            name: (string) $this->validatedString('name'),
            categoryId: (int) $this->validatedInt('categoryId'),
            subCategoryId: (int) $this->validatedInt('subCategoryId'),
            code: $this->validatedString('code'),
            notes: $this->validatedString('notes'),
            purchasable: $this->boolean('purchasable'),
            saleable: $this->boolean('saleable'),
            requestable: $this->boolean('requestable'),
            vat: $this->boolean('vat'),
            luxury: $this->boolean('luxury'),
            receiptTolerance: self::number($this->validated('receiptTolerance')),
            units: array_map(static fn (array $unit): ProductUnitDraft => new ProductUnitDraft(
                uomId: (int) $unit['uomId'],
                qty: self::number($unit['qty']) ?? 0,
                isBase: filter_var($unit['isBase'] ?? false, FILTER_VALIDATE_BOOLEAN),
                purchasable: filter_var($unit['purchasable'] ?? false, FILTER_VALIDATE_BOOLEAN),
                saleable: filter_var($unit['saleable'] ?? false, FILTER_VALIDATE_BOOLEAN),
                stocked: filter_var($unit['stocked'] ?? false, FILTER_VALIDATE_BOOLEAN),
                transferable: filter_var($unit['transferable'] ?? false, FILTER_VALIDATE_BOOLEAN),
                sku: is_string($unit['sku'] ?? null) && $unit['sku'] !== '' ? $unit['sku'] : null,
                basePrice: self::number($unit['basePrice'] ?? null),
                weight: self::number($unit['weight'] ?? null),
                active: filter_var($unit['active'] ?? true, FILTER_VALIDATE_BOOLEAN),
                productDetailId: is_numeric($unit['productDetailId'] ?? null) ? (int) $unit['productDetailId'] : null,
            ), $units),
        );
    }

    /**
     * A validated number as an int or float, keeping "15000" an integer.
     */
    private static function number(mixed $value): int|float|null
    {
        return is_numeric($value) ? $value + 0 : null;
    }
}
