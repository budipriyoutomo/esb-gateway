<?php

namespace App\Integrations\ESB\Mappers;

use App\Domain\Canonical\SupplierCategory;
use App\Domain\Canonical\SupplierCategorySummary;
use App\Integrations\ESB\Clients\SupplierCategoryClient;
use App\Integrations\ESB\Mappers\Concerns\ReadsEsbValues;

/**
 * Maps ESB Core supplier categories onto the canonical SupplierCategory
 * ([ESB-023]).
 */
class SupplierCategoryMapper
{
    use ReadsEsbValues;

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<SupplierCategorySummary>
     */
    public function summariesFromRows(array $rows): array
    {
        return array_map(fn (array $row): SupplierCategorySummary => $this->summaryFromRow($row), $rows);
    }

    /**
     * @param  array<mixed>  $row
     */
    public function summaryFromRow(array $row): SupplierCategorySummary
    {
        return new SupplierCategorySummary(
            provider: SupplierCategoryClient::PROVIDER,
            id: $this->nullableId($row['supplierCategoryID'] ?? null),
            name: $this->stringOr($row['supplierCategoryName'] ?? null, ''),
            active: $this->flag($row['flagActive'] ?? null),
        );
    }

    /**
     * The detail result, or null when it carries no id.
     *
     * @param  array<mixed>  $result
     */
    public function fromDetail(array $result): ?SupplierCategory
    {
        $id = $this->nullableId($result['supplierCategoryID'] ?? null);

        if ($id === null) {
            return null;
        }

        return new SupplierCategory(
            provider: SupplierCategoryClient::PROVIDER,
            id: $id,
            name: $this->stringOr($result['supplierCategoryName'] ?? null, ''),
            active: $this->flag($result['flagActive'] ?? null),
            createdBy: $this->nullableString($result['createdBy'] ?? null),
            createdAt: $this->nullableString($result['createdDate'] ?? null),
            editedBy: $this->nullableString($result['editedBy'] ?? null),
            editedAt: $this->nullableString($result['editedDate'] ?? null),
        );
    }
}
