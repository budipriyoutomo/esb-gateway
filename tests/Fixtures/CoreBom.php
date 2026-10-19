<?php

namespace Tests\Fixtures;

/**
 * ESB Core Master Bill of Material responses ([ESB-014]): GET /product/bom
 * and GET /product/bom/{bomID}, in the shape of the vendor's OpenAPI schema.
 * The vendor's examples are zero-valued, so the values here are realistic
 * stand-ins; only the field names and types are the vendor's.
 */
final class CoreBom
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function row(array $overrides = []): array
    {
        return [
            'bomID' => 412,
            'bomCode' => 'BOM-NG01',
            'bomName' => 'Nasi Goreng Spesial',
            'bomTypeID' => 2,
            'bomTypeName' => 'Menu',
            'productName' => 'Nasi Goreng Spesial',
            'uomName' => 'Porsi',
            'notes' => '',
            'flagActive' => 1,
            ...$overrides,
        ];
    }

    /**
     * @param  list<array<string, mixed>>|null  $rows
     * @return array<string, mixed>
     */
    public static function listResponse(?array $rows = null, int $count = 1, int $page = 1): array
    {
        return [
            'path' => 'https://stg7.esb.co.id/core-int/product/bom',
            'timestamp' => '2026-01-15 10:30:00',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => [
                'count' => $count,
                'data' => $rows ?? [self::row()],
                'limit' => 20,
                'next' => '',
                'page' => $page,
                'prev' => '',
            ],
            'errors' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function detail(array $overrides = []): array
    {
        return [
            'bomID' => 412,
            'bomCode' => 'BOM-NG01',
            'bomName' => 'Nasi Goreng Spesial',
            'bomTypeID' => 2,
            'bomTypeName' => 'Menu',
            'accessType' => 'All',
            'productID' => 75,
            'productCode' => 'NG01',
            'productName' => 'Nasi Goreng Spesial',
            'productDetailID' => 310,
            'uomID' => 4,
            'uomName' => 'Porsi',
            'notes' => 'Resep standar',
            'flagActive' => 1,
            'bomCostTotal' => 18500.5,
            'otherCostTotal' => 1500,
            'weightFactorTotal' => 2,
            'createdBy' => 'admin',
            'createdDate' => '2026-01-10 08:00:00',
            'editedBy' => null,
            'editedDate' => null,
            'bomDetails' => [
                [
                    'ID' => 9001,
                    'bomID' => 412,
                    'productID' => 80,
                    'productDetailID' => 801,
                    'productCode' => 'BRS01',
                    'productName' => 'Beras',
                    'uomID' => 7,
                    'uomName' => 'Gram',
                    'qty' => 200,
                    'uomQty' => 200,
                    'convertionQty' => 1,
                    'lastHpp' => 15.5,
                    'price' => 3100,
                    'printGroup' => '',
                    'tolerancePercent' => 5,
                    'yieldPercent' => 100,
                    'weightFactor' => 1,
                    'subtitution' => [
                        [
                            'ID' => 1,
                            'bomDetailID' => 9001,
                            'bomID' => 412,
                            'orderList' => 1,
                            'productDetailID' => 802,
                            'productCodeSubtitution' => 'BRS02',
                            'productNameSubtitution' => 'Beras Merah',
                            'productNameDetail' => 'Beras',
                            'uomNameSubtitution' => 'Gram',
                        ],
                        // No product detail: not a usable substitute.
                        ['ID' => 2, 'productDetailID' => 0, 'productNameSubtitution' => 'Ghost'],
                    ],
                    'subtitutionManufacturing' => [
                        ['productDetailID' => 803, 'productName' => 'Beras Pandan'],
                    ],
                ],
                [
                    'ID' => 9002,
                    'bomID' => 412,
                    'productID' => 81,
                    'productDetailID' => 811,
                    'productCode' => 'TLR01',
                    'productName' => 'Telur Ayam',
                    'uomID' => 8,
                    'uomName' => 'Butir',
                    'qty' => '1.5',
                    'uomQty' => 1.5,
                    'convertionQty' => 1,
                    'lastHpp' => 2000,
                    'price' => 3000,
                    'printGroup' => 'Topping',
                    'tolerancePercent' => 0,
                    'yieldPercent' => 100,
                    'weightFactor' => 1,
                    'subtitution' => null,
                    'subtitutionManufacturing' => null,
                ],
                // No product: skipped.
                ['ID' => 9003, 'productDetailID' => 0, 'productName' => ''],
            ],
            'bomCosts' => [
                [
                    'ID' => 31,
                    'bomID' => 412,
                    'coaNo' => '5101',
                    'costDescription' => 'Gas',
                    'costTotal' => 1500,
                ],
            ],
            'bomReplacements' => [
                [
                    'ID' => 51,
                    'bomID' => 412,
                    'menuID' => 5564,
                    'menuCode' => 'NG01',
                    'menuName' => 'Nasi Goreng Spesial',
                    'productID' => 81,
                    'productCode' => 'TLR01',
                    'productName' => 'Telur Ayam',
                    'productDetailID' => 811,
                    'replacementProductID' => 82,
                    'replacementProductCode' => 'TLB01',
                    'replacementProductName' => 'Telur Bebek',
                    'replacementProductDetailID' => 821,
                    'uomName' => 'Butir',
                    'qty' => 1,
                ],
            ],
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $detail
     * @return array<string, mixed>
     */
    public static function detailResponse(?array $detail = null): array
    {
        return [
            'path' => 'https://stg7.esb.co.id/core-int/product/bom/412',
            'timestamp' => '2026-01-15 10:30:00',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => $detail ?? self::detail(),
            'errors' => null,
        ];
    }
}
