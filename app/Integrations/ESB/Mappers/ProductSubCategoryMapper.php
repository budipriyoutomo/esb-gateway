<?php

namespace App\Integrations\ESB\Mappers;

use App\Domain\Canonical\ProductSubCategory;
use App\Domain\Canonical\ProductSubCategoryDraft;
use App\Domain\Canonical\ProductSubCategorySummary;
use App\Integrations\ESB\Clients\ProductSubCategoryClient;
use App\Integrations\ESB\Mappers\Concerns\ReadsEsbValues;

/**
 * Maps ESB Core product sub-categories onto the canonical ProductSubCategory
 * ([ESB-022]).
 */
class ProductSubCategoryMapper
{
    use ReadsEsbValues;

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<ProductSubCategorySummary>
     */
    public function summariesFromRows(array $rows): array
    {
        return array_map(fn (array $row): ProductSubCategorySummary => $this->summaryFromRow($row), $rows);
    }

    /**
     * @param  array<mixed>  $row
     */
    public function summaryFromRow(array $row): ProductSubCategorySummary
    {
        return new ProductSubCategorySummary(
            provider: ProductSubCategoryClient::PROVIDER,
            id: $this->nullableId($row['subCategoryID'] ?? null),
            name: $this->stringOr($row['subCategoryName'] ?? null, ''),
            deadStockDays: $this->positiveIntOrNull($row['deadStockThreshold'] ?? null),
            notes: $this->nullableString($row['notes'] ?? null),
            active: $this->flag($row['flagActive'] ?? null),
        );
    }

    /**
     * The detail result, or null when it carries no id.
     *
     * @param  array<mixed>  $result
     */
    public function fromDetail(array $result): ?ProductSubCategory
    {
        $id = $this->nullableId($result['subCategoryID'] ?? null);

        if ($id === null) {
            return null;
        }

        return new ProductSubCategory(
            provider: ProductSubCategoryClient::PROVIDER,
            id: $id,
            name: $this->stringOr($result['subCategoryName'] ?? null, ''),
            deadStockDays: $this->positiveIntOrNull($result['deadStockThreshold'] ?? null),
            notes: $this->nullableString($result['notes'] ?? null),
            active: $this->flag($result['flagActive'] ?? null),
            createdBy: $this->nullableString($result['createdBy'] ?? null),
            createdAt: $this->nullableString($result['createdDate'] ?? null),
            editedBy: $this->nullableString($result['editedBy'] ?? null),
            editedAt: $this->nullableString($result['editedDate'] ?? null),
        );
    }

    /**
     * The body of POST /product/sub-category and PUT .../{subCategoryID}.
     * flagActive is not sent: deactivate / restore change it.
     *
     * @return array<string, int|string>
     */
    public function requestFrom(ProductSubCategoryDraft $draft): array
    {
        return [
            'subCategoryName' => $draft->name,
            'deadStockThreshold' => $draft->deadStockDays,
            'notes' => $draft->notes ?? '',
        ];
    }
}
