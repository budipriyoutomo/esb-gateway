<?php

namespace App\Integrations\ESB\Mappers;

use App\Domain\Canonical\ProductCategory;
use App\Domain\Canonical\ProductCategoryQuery;
use App\Domain\Canonical\ProductCategorySummary;
use App\Integrations\ESB\Clients\ProductCategoryClient;
use App\Integrations\ESB\Mappers\Concerns\ReadsEsbValues;

/**
 * Maps ESB Core product categories onto the canonical ProductCategory
 * ([ESB-021]).
 */
class ProductCategoryMapper
{
    use ReadsEsbValues;

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<ProductCategorySummary>
     */
    public function summariesFromRows(array $rows): array
    {
        return array_map(fn (array $row): ProductCategorySummary => $this->summaryFromRow($row), $rows);
    }

    /**
     * @param  array<mixed>  $row
     */
    public function summaryFromRow(array $row): ProductCategorySummary
    {
        return new ProductCategorySummary(
            provider: ProductCategoryClient::PROVIDER,
            id: $this->nullableId($row['categoryID'] ?? null),
            name: $this->stringOr($row['categoryName'] ?? null, ''),
            typeId: $this->nullableId($row['categoryTypeID'] ?? null),
            typeName: $this->nullableString($row['categoryTypeName'] ?? null),
            notes: $this->nullableString($row['notes'] ?? null),
            active: $this->flag($row['flagActive'] ?? null),
        );
    }

    /**
     * The detail result, or null when it carries no id. The detail has no
     * type name, so it is taken from the vendor's legend.
     *
     * @param  array<mixed>  $result
     */
    public function fromDetail(array $result): ?ProductCategory
    {
        $id = $this->nullableId($result['categoryID'] ?? null);

        if ($id === null) {
            return null;
        }

        $typeId = $this->nullableId($result['categoryTypeID'] ?? null);

        return new ProductCategory(
            provider: ProductCategoryClient::PROVIDER,
            id: $id,
            name: $this->stringOr($result['categoryName'] ?? null, ''),
            code: $this->nullableString($result['categoryCode'] ?? null),
            typeId: $typeId,
            typeName: $typeId === null ? null : (ProductCategoryQuery::TYPES[$typeId] ?? null),
            notes: $this->nullableString($result['notes'] ?? null),
            active: $this->flag($result['flagActive'] ?? null),
            coaNo: $this->nullableString($result['coaNo'] ?? null),
            cogsCoaNo: $this->nullableString($result['cogsCoaNo'] ?? null),
            accrualBasisCoaNo: $this->nullableString($result['accrualBasisCoaNo'] ?? null),
            opnameCoaNo: $this->nullableString($result['opnameCoaNo'] ?? null),
            productSalesCoaNo: $this->nullableString($result['productSalesCoaNo'] ?? null),
            depreciationCoaNo: $this->nullableString($result['depCoaNo'] ?? null),
            depreciationExpenseCoaNo: $this->nullableString($result['depExpenseCoaNo'] ?? null),
            depreciationMonths: $this->positiveIntOrNull($result['depLength'] ?? null),
            fiscalDepreciationMonths: $this->positiveIntOrNull($result['depLengthFiscal'] ?? null),
            depreciationTypeId: $this->nullableId($result['depreciationTypeID'] ?? null),
            fiscalDepreciationTypeId: $this->nullableId($result['depreciationTypeIDFiscal'] ?? null),
            assetTax: $this->flag($result['flagAssetTax'] ?? null),
            createdBy: $this->nullableString($result['createdBy'] ?? null),
            createdAt: $this->nullableString($result['createdDate'] ?? null),
            editedBy: $this->nullableString($result['editedBy'] ?? null),
            editedAt: $this->nullableString($result['editedDate'] ?? null),
        );
    }
}
