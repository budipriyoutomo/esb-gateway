<?php

namespace Tests\Fixtures;

/**
 * ESB Core product categories ([ESB-021]): GET /product/category and
 * GET /product/category/{categoryID}. Realistic stand-ins for the vendor's
 * zero-valued examples; field names and types are the vendor's.
 */
final class CoreProductCategory
{
    use EsbEnvelope;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function row(array $overrides = []): array
    {
        return [
            'categoryID' => 3,
            'categoryName' => 'Bahan Baku',
            'categoryTypeID' => 1,
            'categoryTypeName' => 'Inventory',
            'flagActive' => 1,
            'notes' => '',
            ...$overrides,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function listResponse(int $count = 1, int $page = 1): array
    {
        return self::paginated('/product/category', [self::row()], $count, $page);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function detailResponse(array $overrides = []): array
    {
        return self::envelope('/product/category/3', [
            'categoryID' => 3,
            'categoryCode' => 'BB',
            'categoryName' => 'Bahan Baku',
            'categoryTypeID' => 1,
            'coaNo' => '1301',
            'cogsCoaNo' => '5101',
            'accrualBasisCoaNo' => '',
            'opnameCoaNo' => '5901',
            'productSalesCoaNo' => '',
            'depCoaNo' => '',
            'depExpenseCoaNo' => '',
            'depLength' => 0,
            'depLengthFiscal' => 0,
            'depreciationTypeID' => 0,
            'depreciationTypeIDFiscal' => 0,
            'flagAssetTax' => 0,
            'notes' => 'Raw materials',
            'flagActive' => 1,
            'createdBy' => 'admin',
            'createdDate' => '2026-01-10 08:00:00',
            'editedBy' => 'budi',
            'editedDate' => '2026-02-01 13:30:00',
            ...$overrides,
        ]);
    }
}
