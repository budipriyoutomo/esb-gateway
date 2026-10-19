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
