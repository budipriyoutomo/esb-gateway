<?php

namespace App\Integrations\ESB\Mappers;

use App\Integrations\ESB\DTOs\ProductSummary;

/**
 * Maps rows from GET /product/list onto ProductSummary.
 *
 * ESB responses are untrusted input (docs/10-security.md), so every field is
 * read defensively even where the vendor documents it as required: a row that
 * omits one must not take down a page render.
 */
class ProductMapper
{
    /**
     * @param  array<string, mixed>  $row
     */
    public function fromRow(array $row): ProductSummary
    {
        return new ProductSummary(
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
     * @return list<ProductSummary>
     */
    public function fromRows(array $rows): array
    {
        return array_map(fn (array $row): ProductSummary => $this->fromRow($row), $rows);
    }

    private function intOr(mixed $value, int $default): int
    {
        return is_numeric($value) ? (int) $value : $default;
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function stringOr(mixed $value, string $default): string
    {
        return is_string($value) ? $value : $default;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
