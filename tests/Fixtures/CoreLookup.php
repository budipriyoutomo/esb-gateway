<?php

namespace Tests\Fixtures;

/**
 * ESB Core lookup responses ([ESB-019]), in the shape of the vendor's OpenAPI
 * schema. The vendor's examples are zero-valued, so the values here are
 * realistic stand-ins; only the field names and types are the vendor's.
 */
final class CoreLookup
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function branches(): array
    {
        return [
            ['branchID' => 1, 'branchCode' => 'JKT', 'branchName' => 'Jakarta Pusat'],
            ['branchID' => 2, 'branchCode' => 'BDG', 'branchName' => 'Bandung'],
            ['branchID' => 3, 'branchCode' => 'SBY', 'branchName' => 'Surabaya'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function suppliers(): array
    {
        return [
            ['supplierID' => 10, 'supplierCode' => 'SUP-001', 'supplierName' => 'PT Sumber Pangan'],
            ['supplierID' => 11, 'supplierCode' => '', 'supplierName' => 'CV Maju Jaya'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function locations(): array
    {
        return [
            ['locationID' => 100, 'locationName' => 'Gudang Utama', 'branchID' => 1, 'locationTypeID' => 1, 'flagActive' => 1, 'allowMinusStock' => 0],
            ['locationID' => 101, 'locationName' => 'Dapur', 'branchID' => 1, 'locationTypeID' => 2, 'flagActive' => 1, 'allowMinusStock' => 1],
            ['locationID' => 200, 'locationName' => 'Gudang Bandung', 'branchID' => 2, 'locationTypeID' => 1, 'flagActive' => 1, 'allowMinusStock' => 0],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function cashBankAccounts(): array
    {
        return [
            ['coaNo' => '1101', 'description' => 'Kas Jakarta', 'branchID' => 1, 'flagActive' => 1],
            ['coaNo' => '1102', 'description' => 'Bank BCA Jakarta', 'branchID' => 1, 'flagActive' => 1],
            ['coaNo' => '1201', 'description' => 'Kas Bandung', 'branchID' => 2, 'flagActive' => 1],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function statuses(): array
    {
        return [
            ['statusID' => 1, 'statusName' => 'New'],
            ['statusID' => 2, 'statusName' => 'Authorized'],
            ['statusID' => 3, 'statusName' => 'Rejected'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function coaAccounts(): array
    {
        return [
            ['coaNo' => '6101', 'description' => 'Biaya Listrik'],
            ['coaNo' => '6102', 'description' => 'Biaya Air'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function transferProducts(): array
    {
        return [
            [
                'barcodeNumber' => '8991234567890',
                'categoryID' => 3,
                'categoryName' => 'Bahan Baku',
                'categoryTypeID' => 1,
                'lastHpp' => 15000,
                'minQty' => 0,
                'pendingQty' => 2,
                'productCode' => 'BB-001',
                'productDetailID' => 501,
                'productID' => 50,
                'productName' => 'Beras Premium',
                'qty' => 1,
                'stockQty' => 120.5,
                'subCategoryID' => 7,
                'subCategoryName' => 'Beras',
                'uomID' => 4,
                'uomName' => 'KG',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function metrics(): array
    {
        return [
            ['metricID' => 1, 'metricName' => 'Weight'],
            ['metricID' => 2, 'metricName' => 'Volume'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function categoryTypes(): array
    {
        return [
            ['categoryTypeID' => 1, 'categoryTypeName' => 'Inventory'],
            ['categoryTypeID' => 2, 'categoryTypeName' => 'Asset'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function taxes(): array
    {
        return [
            ['taxID' => 1, 'taxName' => 'PPN 11%', 'taxRate' => 11, 'flagActive' => true, 'coaNo' => '2101', 'coaDescription' => 'PPN Keluaran', 'flagCustoms' => false],
            ['taxID' => 2, 'taxName' => 'PPN Lama', 'taxRate' => 10, 'flagActive' => false, 'coaNo' => '', 'coaDescription' => '', 'flagCustoms' => false],
            ['taxID' => 3, 'taxName' => 'PB1', 'taxRate' => 10, 'flagActive' => true, 'coaNo' => '', 'coaDescription' => '', 'flagCustoms' => false],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function productCategories(): array
    {
        return [
            ['categoryID' => 3, 'categoryCode' => 'BB', 'categoryName' => 'Bahan Baku', 'categoryTypeID' => 1, 'flagActive' => 1],
            ['categoryID' => 4, 'categoryCode' => 'OLD', 'categoryName' => 'Lama', 'categoryTypeID' => 1, 'flagActive' => 0],
            ['categoryID' => 5, 'categoryCode' => '', 'categoryName' => 'Kemasan', 'categoryTypeID' => 1, 'flagActive' => 1],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function productSubCategories(): array
    {
        return [
            ['subCategoryID' => 7, 'subCategoryName' => 'Beras', 'deadStockThreshold' => 90, 'flagActive' => 1, 'notes' => ''],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function units(): array
    {
        return [
            ['uomID' => 4, 'uomName' => 'KG'],
            ['uomID' => 9, 'uomName' => 'SAK'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function currencies(): array
    {
        return [
            ['currencyID' => 1, 'currencyName' => 'Rupiah', 'currencySign' => 'IDR'],
            ['currencyID' => 2, 'currencyName' => 'US Dollar', 'currencySign' => 'USD'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function productDetails(): array
    {
        return [
            ['productDetailID' => 501, 'productID' => 50, 'productCode' => 'BB-001', 'productName' => 'Beras Premium', 'qty' => 1, 'uomID' => 4, 'uomName' => 'KG'],
            ['productDetailID' => 502, 'productID' => 50, 'productCode' => 'BB-001', 'productName' => 'Beras Premium', 'qty' => 25, 'uomID' => 9, 'uomName' => 'SAK'],
        ];
    }

    /**
     * GET /purpose rows (paginated). purposeAppliedTo lists where a purpose
     * may be used; its values are undocumented, so the gateway does not
     * filter on it.
     *
     * @return list<array<string, mixed>>
     */
    public static function purposes(): array
    {
        return [
            ['flagActive' => true, 'purposeAccount' => 'Beban Lain-lain', 'purposeAppliedTo' => ['Item Journal'], 'purposeCoaNo' => '6101', 'purposeID' => 7, 'purposeName' => 'Waste'],
            ['flagActive' => false, 'purposeAccount' => '', 'purposeAppliedTo' => [], 'purposeCoaNo' => '', 'purposeID' => 8, 'purposeName' => 'Old purpose'],
        ];
    }

    /**
     * An "all rows, no pagination" response.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public static function listResponse(array $rows, string $path = '/branch'): array
    {
        return [
            'path' => 'https://stg7.esb.co.id/core-int'.$path,
            'timestamp' => '2026-01-15 10:30:00',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => $rows,
            'errors' => null,
        ];
    }

    /**
     * A paginated response.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public static function pageResponse(array $rows, int $count, int $page = 1, int $limit = 20, string $path = '/accounting/coa/active-non-module'): array
    {
        return [
            'path' => 'https://stg7.esb.co.id/core-int'.$path,
            'timestamp' => '2026-01-15 10:30:00',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => [
                'count' => $count,
                'data' => $rows,
                'limit' => $limit,
                'next' => '',
                'page' => $page,
                'prev' => '',
            ],
            'errors' => null,
        ];
    }
}
