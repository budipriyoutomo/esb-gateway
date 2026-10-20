<?php

namespace App\Integrations\ESB\Mappers;

use App\Actions\Support\CreatedRecord;
use App\Domain\Canonical\Product;
use App\Domain\Canonical\ProductDetail;
use App\Domain\Canonical\ProductDraft;
use App\Domain\Canonical\ProductUnit;
use App\Integrations\ESB\Clients\ProductClient;
use App\Integrations\ESB\Mappers\Concerns\ReadsEsbValues;

/**
 * Maps rows from GET /product/list onto the canonical Product, and
 * GET /product/{productID} onto ProductDetail ([ESB-026]).
 *
 * ESB responses are untrusted input (docs/10-security.md), so every field is
 * read defensively even where the vendor documents it as required: a row that
 * omits one must not take down a page render.
 */
class ProductMapper
{
    use ReadsEsbValues;

    /**
     * @param  array<string, mixed>  $row
     */
    public function fromRow(array $row): Product
    {
        return new Product(
            provider: ProductClient::PROVIDER,
            id: $this->intOr($row['productID'] ?? null, 0),
            name: $this->stringOr($row['productName'] ?? null, ''),
            code: $this->stringOr($row['productCode'] ?? null, ''),
            categoryId: $this->nullableInt($row['categoryID'] ?? null),
            categoryName: $this->nullableString($row['categoryName'] ?? null),
            subCategoryId: $this->nullableInt($row['subCategoryID'] ?? null),
            subCategoryName: $this->nullableString($row['subCategoryName'] ?? null),
            bomId: $this->nullableInt($row['bomID'] ?? null),
            bomName: $this->nullableString($row['bomName'] ?? null),
            categoryTypeId: $this->nullableInt($row['categoryTypeID'] ?? null),
            categoryTypeName: $this->nullableString($row['categoryTypeName'] ?? null),
            // The vendor sends 1 for active and 0 for inactive.
            isActive: $this->intOr($row['flagActive'] ?? null, 0) === 1,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<Product>
     */
    public function fromRows(array $rows): array
    {
        return array_map(fn (array $row): Product => $this->fromRow($row), $rows);
    }

    /**
     * The detail result, or null when it carries no id. Units without a
     * product detail id are dropped: nothing could point at them.
     *
     * @param  array<mixed>  $result
     */
    public function fromDetail(array $result): ?ProductDetail
    {
        $id = $this->nullableId($result['productID'] ?? null);

        if ($id === null) {
            return null;
        }

        $units = [];

        foreach (is_array($result['productDetails'] ?? null) ? $result['productDetails'] : [] as $unit) {
            $detailId = is_array($unit) ? $this->nullableId($unit['productDetailID'] ?? null) : null;

            if ($detailId === null) {
                continue;
            }

            $units[] = new ProductUnit(
                productDetailId: $detailId,
                uomId: $this->nullableId($unit['uomID'] ?? null),
                uomName: $this->nullableString($unit['uomName'] ?? null),
                qty: $this->nullableFloat($unit['qty'] ?? null),
                isBase: $this->flag($unit['isBase'] ?? null),
                purchasable: $this->flag($unit['isPurchase'] ?? null),
                saleable: $this->flag($unit['isSales'] ?? null),
                stocked: $this->flag($unit['isStock'] ?? null),
                transferable: $this->flag($unit['isTransfer'] ?? null),
                sku: $this->nullableString($unit['SKU'] ?? null),
                basePrice: $this->nullableFloat($unit['basePrice'] ?? null),
                weight: $this->nullableFloat($unit['weight'] ?? null),
                menuName: $this->nullableString($unit['menuName'] ?? null),
                menuId: $this->nullableId($unit['menuID'] ?? null),
                cubication: $this->nullableFloat($unit['cubication'] ?? null),
                active: $this->flag($unit['flagActive'] ?? null),
            );
        }

        return new ProductDetail(
            provider: ProductClient::PROVIDER,
            id: $id,
            name: $this->stringOr($result['productName'] ?? null, ''),
            code: $this->nullableString($result['productCode'] ?? null),
            categoryId: $this->nullableId($result['categoryID'] ?? null),
            categoryName: $this->nullableString($result['categoryName'] ?? null),
            subCategoryId: $this->nullableId($result['subCategoryID'] ?? null),
            subCategoryName: $this->nullableString($result['subCategoryName'] ?? null),
            bomId: $this->nullableId($result['bomID'] ?? null),
            bomName: $this->nullableString($result['bomName'] ?? null),
            purchasable: $this->flag($result['purchasable'] ?? null),
            saleable: $this->flag($result['saleable'] ?? null),
            requestable: $this->flag($result['requestable'] ?? null),
            vat: $this->flag($result['VAT'] ?? null),
            luxury: $this->flag($result['flagLuxuryItem'] ?? null),
            receiptTolerance: $this->nullableFloat($result['receiptTolerance'] ?? null),
            coretaxCode: $this->nullableString($result['coretaxProductCode'] ?? null),
            notes: $this->nullableString($result['notes'] ?? null),
            active: $this->flag($result['flagActive'] ?? null),
            units: $units,
            coretaxCodeId: $this->nullableId($result['coretaxProductCodeID'] ?? null),
            customFields: $this->customFields($result['customFields'] ?? null),
            createdBy: $this->nullableString($result['createdBy'] ?? null),
            createdAt: $this->nullableString($result['createdDate'] ?? null),
            editedBy: $this->nullableString($result['editedBy'] ?? null),
            editedAt: $this->nullableString($result['editedDate'] ?? null),
        );
    }

    /**
     * The body of POST /product and PUT /product/{productID}. Every field the
     * form edits is sent, an empty one as the vendor's own "none" ("" / 0).
     * PUT replaces the product, so on an update what the form does not edit
     * -- BOM, CoreTax code, custom fields, and each existing unit's menu and
     * cubication -- is carried over from $current. pushToGoApp is never read,
     * so it is never sent (TBD).
     *
     * @return array<string, mixed>
     */
    public function requestFrom(ProductDraft $draft, ?ProductDetail $current = null): array
    {
        $existing = [];

        foreach ($current === null ? [] : $current->units as $unit) {
            $existing[$unit->productDetailId] = $unit;
        }

        $units = [];

        foreach ($draft->units as $unit) {
            $kept = $unit->productDetailId === null ? null : ($existing[$unit->productDetailId] ?? null);

            $units[] = [
                ...($unit->productDetailId === null ? [] : ['productDetailID' => $unit->productDetailId]),
                'uomID' => $unit->uomId,
                'qty' => $unit->qty,
                'isBase' => $unit->isBase,
                'isPurchase' => $unit->purchasable,
                'isSales' => $unit->saleable,
                'isStock' => $unit->stocked,
                'isTransfer' => $unit->transferable,
                'sku' => $unit->sku ?? '',
                'basePrice' => $unit->basePrice ?? 0,
                'weight' => $unit->weight ?? 0,
                'flagActive' => $unit->active,
                ...($kept?->menuId === null ? [] : ['menuID' => $kept->menuId]),
                ...($kept?->cubication === null ? [] : ['cubication' => $kept->cubication]),
            ];
        }

        return [
            'productName' => $draft->name,
            'productCode' => $draft->code ?? '',
            'categoryID' => $draft->categoryId,
            'subCategoryID' => $draft->subCategoryId,
            'notes' => $draft->notes ?? '',
            'purchasable' => $draft->purchasable,
            'saleable' => $draft->saleable,
            'requestable' => $draft->requestable,
            'vat' => $draft->vat,
            'flagLuxuryItem' => $draft->luxury ? 1 : 0,
            'receiptTolerance' => $draft->receiptTolerance ?? 0,
            ...($current?->bomId === null ? [] : ['bomID' => $current->bomId]),
            ...($current?->coretaxCodeId === null ? [] : ['coretaxProductCodeID' => $current->coretaxCodeId]),
            ...($current === null || $current->customFields === [] ? [] : ['customFields' => $current->customFields]),
            'productDetails' => $units,
        ];
    }

    /**
     * What a create answered: the new id, and whether the product went to
     * the ERP's approval queue (isTemp) instead.
     *
     * @param  array<mixed>  $result
     */
    public function createdFrom(array $result): CreatedRecord
    {
        return new CreatedRecord(
            id: $this->nullableId($result['productID'] ?? null),
            pendingApproval: ($result['isTemp'] ?? false) === true,
        );
    }

    /**
     * Custom fields as the ERP keyed them; only text (or nothing) is a value.
     *
     * @return array<string, string|null>
     */
    private function customFields(mixed $fields): array
    {
        if (! is_array($fields)) {
            return [];
        }

        $kept = [];

        foreach ($fields as $key => $value) {
            if (is_string($key) && (is_string($value) || $value === null)) {
                $kept[$key] = $value;
            }
        }

        return $kept;
    }

    private function intOr(mixed $value, int $default): int
    {
        return is_numeric($value) ? (int) $value : $default;
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
