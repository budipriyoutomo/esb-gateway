<?php

namespace Tests\Fixtures;

/**
 * POST /purchase/request-monitoring, shaped as staging answered it on
 * 2026-10-28: the paginated envelope with page / limit 0 and every row.
 */
final class CoreRequestMonitoring
{
    public const PR = 'PR202603180002';

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function row(array $overrides = []): array
    {
        return [
            'branchID' => 3,
            'branchName' => 'Outlet Senopati',
            'categoryName' => 'Beverage',
            'categoryType' => 'Inventory',
            'categoryTypeID' => 1,
            'convertionQty' => 1,
            'costCenterID' => null,
            'costCenterName' => null,
            'flagLuxuryItem' => 0,
            'minPrice' => null,
            'productCode' => 'Air_mineral',
            'productDetailID' => 901,
            'productID' => 90,
            'productName' => 'Air mineral',
            'projectID' => null,
            'projectName' => null,
            'purchaseRequestDate' => '2026-03-18',
            'purchaseRequestNum' => self::PR,
            'requestProcessID' => 2,
            'requestProcessName' => 'Purchase',
            'requiredDate' => '2026-03-25',
            'subCategoryName' => 'Water',
            'sumQty' => 20,
            'supplierName' => 'PT Sumber Air',
            'uomName' => 'Bottle',
            'vat' => false,
            ...$overrides,
        ];
    }

    /**
     * @param  list<array<string, mixed>>|null  $rows
     * @return array<string, mixed>
     */
    public static function response(?array $rows = null): array
    {
        $rows ??= [self::row()];

        return [
            'path' => 'https://stg7.esb.co.id/core-int/purchase/request-monitoring',
            'timestamp' => '2026-03-26 10:30:00',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => ['page' => 0, 'limit' => 0, 'count' => count($rows), 'data' => $rows, 'prev' => '', 'next' => ''],
            'errors' => null,
        ];
    }
}
