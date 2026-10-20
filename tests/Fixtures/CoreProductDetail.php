<?php

namespace Tests\Fixtures;

/**
 * ESB Core GET /product/{productID} ([ESB-026]). Realistic stand-ins for the
 * vendor's zero-valued examples; field names and types are the vendor's.
 */
final class CoreProductDetail
{
    use EsbEnvelope;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function unit(array $overrides = []): array
    {
        return [
            'productDetailID' => 501,
            'uomID' => 4,
            'uomName' => 'KG',
            'qty' => 1,
            'baseUnit' => 'KG',
            'isBase' => true,
            'isPurchase' => true,
            'isSales' => false,
            'isStock' => true,
            'isTransfer' => true,
            'SKU' => 'BRS-PRM-KG',
            'basePrice' => 15000.5,
            'weight' => 1,
            'cubication' => 0,
            'flagActive' => true,
            'canEditSKU' => false,
            'goAppProduct' => false,
            'isUsedInTransaction' => true,
            'menuID' => null,
            'menuName' => null,
            'metricID' => 2,
            'productDetailMenus' => [],
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function detailResponse(array $overrides = []): array
    {
        return self::envelope('/product/50', [
            'productID' => 50,
            'productCode' => 'BB-001',
            'productName' => 'Beras Premium',
            'categoryID' => 3,
            'categoryName' => 'Bahan Baku',
            'subCategoryID' => 7,
            'subCategoryName' => 'Beras',
            'bomID' => null,
            'bomName' => null,
            'purchasable' => true,
            'saleable' => false,
            'requestable' => true,
            'VAT' => true,
            'flagLuxuryItem' => 0,
            'receiptTolerance' => 2.5,
            'coretaxProductCode' => '',
            'coretaxProductCodeID' => null,
            'notes' => '',
            'flagActive' => true,
            'customFields' => ['field1' => null, 'field2' => null, 'field3' => null, 'field4' => null, 'field5' => null],
            'createdBy' => 'admin',
            'createdDate' => '2026-01-10 08:00:00',
            'editedBy' => null,
            'editedDate' => null,
            'productDetails' => [
                self::unit(),
                self::unit([
                    'productDetailID' => 502,
                    'uomID' => 9,
                    'uomName' => 'SAK',
                    'qty' => 25,
                    'isBase' => false,
                    'SKU' => '',
                    'basePrice' => 370000,
                ]),
            ],
            ...$overrides,
        ]);
    }
}
