<?php

namespace App\Http\Requests\Pricelists;

use App\Authorization\Permission;
use App\Domain\Canonical\PricelistDraft;
use App\Domain\Canonical\PricelistDraftLine;
use App\Http\Requests\BaseFormRequest;

/**
 * A pricelist to propose, or to replace a proposed one with ([ESB-031]).
 *
 * Mirrors the vendor schema of POST /pricelist (PUT /pricelist/temp/{num}
 * takes the same): supplier, currency and both dates required, YYYY-MM-DD;
 * at least one line, each product detail once, price >= 0 with at most four
 * decimals (decimal(20,4)). The expiry is held to on or after the price date
 * (assumed; the vendor states no order). supplierName is display only.
 */
class SavePricelistRequest extends BaseFormRequest
{
    /** A bound on lines; the vendor documents none. */
    public const MAX_LINES = 500;

    public function authorize(): bool
    {
        return $this->userHoldsAll(Permission::InvokeEsbOperations, Permission::ManagePricelists);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'supplierId' => ['required', 'integer', 'min:1'],
            'supplierName' => ['nullable', 'string', 'max:120'],
            'currencyId' => ['required', 'integer', 'min:1'],
            'priceDate' => ['required', 'date_format:Y-m-d'],
            'expireDate' => ['required', 'date_format:Y-m-d', 'after_or_equal:priceDate'],
            'branchIds' => ['present', 'array', 'max:500'],
            'branchIds.*' => ['integer', 'min:1', 'distinct'],
            'lines' => ['required', 'array', 'min:1', 'max:'.self::MAX_LINES],
            'lines.*.productDetailId' => ['required', 'integer', 'min:1', 'distinct'],
            'lines.*.price' => ['required', 'numeric', 'min:0', 'max:9999999999999999', 'decimal:0,4'],
        ];
    }

    public function toDraft(): PricelistDraft
    {
        /** @var list<array{productDetailId: int|string, price: int|float|string}> $lines */
        $lines = $this->validated('lines');
        /** @var list<int|string> $branchIds */
        $branchIds = $this->validated('branchIds');

        return new PricelistDraft(
            supplierId: (int) $this->validatedInt('supplierId'),
            currencyId: (int) $this->validatedInt('currencyId'),
            priceDate: (string) $this->validatedString('priceDate'),
            expireDate: (string) $this->validatedString('expireDate'),
            branchIds: array_map('intval', $branchIds),
            lines: array_map(
                static fn (array $line): PricelistDraftLine => new PricelistDraftLine(
                    productDetailId: (int) $line['productDetailId'],
                    price: is_numeric($line['price']) ? $line['price'] + 0 : 0,
                ),
                $lines,
            ),
        );
    }

    /**
     * "for CV Maju Jaya" -- the new pricelist has no number yet.
     */
    public function displayName(): string
    {
        $supplier = $this->validatedString('supplierName');

        return 'for '.($supplier ?? 'supplier #'.$this->validatedInt('supplierId'));
    }
}
