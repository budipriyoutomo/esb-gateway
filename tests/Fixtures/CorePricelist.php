<?php

namespace Tests\Fixtures;

/**
 * ESB Core pricelists ([ESB-027]): GET /pricelist, GET /pricelist/temp and
 * GET /pricelist/temp/{pricelistNum}. Realistic stand-ins for the vendor's
 * zero-valued examples; field names and types are the vendor's.
 */
final class CorePricelist
{
    use EsbEnvelope;

    public const TEMP_NUMBER = 'TPL202603200001';

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function row(array $overrides = []): array
    {
        return [
            'ID' => 812,
            'pricelistNum' => 'PL202603010004',
            'productID' => 50,
            'productDetailID' => 501,
            'productCode' => 'BB-001',
            'productName' => 'Beras Premium',
            'uomID' => 4,
            'unit' => 'KG',
            'supplierID' => 10,
            'supplierName' => 'PT Sumber Pangan',
            'currencyID' => 1,
            'currencyName' => 'IDR',
            'price' => 14500,
            'priceDate' => '2026-03-01',
            'expireDate' => '',
            'applicableBranch' => ['type' => 'ALL', 'branches' => []],
            ...$overrides,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function listResponse(int $count = 1, int $page = 1): array
    {
        return self::paginated('/pricelist', [self::row()], $count, $page);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function tempRow(array $overrides = []): array
    {
        return [
            'pricelistNum' => self::TEMP_NUMBER,
            'supplierName' => 'PT Sumber Pangan',
            'currencyName' => 'IDR',
            'priceDate' => '2026-03-20',
            'expireDate' => '2026-06-30',
            'statusID' => 38,
            'statusName' => 'Waiting For Approval',
            'applicableBranch' => [
                'type' => 'SPECIFIC',
                'branches' => [
                    ['branchID' => 1, 'branchCode' => 'JKT', 'branchName' => 'Jakarta Pusat', 'flagActive' => 1],
                ],
            ],
            ...$overrides,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function tempListResponse(int $count = 1, int $page = 1): array
    {
        return self::paginated('/pricelist/temp', [self::tempRow()], $count, $page);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function tempDetailResponse(array $overrides = []): array
    {
        return self::envelope('/pricelist/temp/'.self::TEMP_NUMBER, [
            ...self::tempRow(),
            'supplierID' => 10,
            'currencyID' => 1,
            'pricelistDetails' => [
                [
                    'productDetailID' => 501,
                    'productCode' => 'BB-001',
                    'productName' => 'Beras Premium',
                    'unitName' => 'KG',
                    'price' => 15000,
                    'lastPrice' => 14500,
                ],
            ],
            'createdBy' => 'budi',
            'createdDate' => '2026-03-20 09:15:00',
            'editedBy' => null,
            'editedDate' => null,
            'rejectedBy' => null,
            'rejectedDate' => null,
            ...$overrides,
        ]);
    }
}
