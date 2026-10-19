<?php

namespace Tests\Fixtures;

/**
 * ESB Core Simple Purchase responses ([ESB-018]): GET /purchase/simple-purchase
 * and GET /purchase/simple-purchase/{cashPurchaseNum}, in the shape of the
 * vendor's OpenAPI schema. The vendor's examples are zero-valued, so the
 * values here are realistic stand-ins; only the field names and types are the
 * vendor's.
 */
final class CoreSimplePurchase
{
    public const NUMBER = 'CP202603200001';

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function row(array $overrides = []): array
    {
        return [
            'additionalInfo' => 'Market run',
            'approval' => [],
            'approvalFlow' => [],
            'branchID' => 3,
            'branchName' => 'Outlet Senopati',
            'cashPurchaseDate' => '2026-03-20',
            'cashPurchaseNum' => self::NUMBER,
            'cashPurchaseTotal' => 255000.5,
            'createdBy' => 'andi',
            'currencySign' => 'Rp',
            'statusID' => 1,
            'statusName' => 'New',
            'supplierID' => 14,
            'supplierInvoiceNum' => 'NOTA-0331',
            'supplierName' => 'Pasar Santa',
            ...$overrides,
        ];
    }

    /**
     * @param  list<array<string, mixed>>|null  $rows
     * @return array<string, mixed>
     */
    public static function listResponse(?array $rows = null, int $count = 1): array
    {
        return [
            'path' => 'https://stg7.esb.co.id/core-int/purchase/simple-purchase',
            'timestamp' => '2026-03-20 10:30:00',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => [
                'count' => $count,
                'data' => $rows ?? [self::row()],
                'limit' => 20,
                'next' => 'https://stg7.esb.co.id/core-int/purchase/simple-purchase?page=2&limit=20',
                'page' => 1,
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
            'additionalInfo' => 'Market run',
            'approval' => [],
            'approvalFlow' => [],
            'authorizedBy' => '',
            'authorizedDate' => '',
            'authorizer' => ['fullName' => '', 'username' => ''],
            'branchFlagActive' => 1,
            'branchID' => 3,
            'branchName' => 'Outlet Senopati',
            'cashPurchaseDate' => '2026-03-20',
            'cashPurchaseDueDate' => '2026-03-20',
            'cashPurchaseNum' => self::NUMBER,
            'cashPurchaseTotal' => 255000,
            'coaNo' => '1101',
            'costCenterID' => 5,
            'costCenterName' => 'Kitchen',
            'costTotal' => 15000,
            'createdBy' => 'andi',
            'createdDate' => '2026-03-20 09:15:00',
            'creator' => ['fullName' => 'Andi Wijaya', 'username' => 'andi'],
            'currencyID' => 1,
            'currencyName' => 'Rupiah',
            'currencySign' => 'Rp',
            'description' => '',
            'dueDate' => 0,
            'editedBy' => '',
            'editedDate' => '',
            'editor' => ['fullName' => '', 'username' => ''],
            'exportCsvCounter' => 0,
            'image' => [
                ['ID' => 1, 'imageUrl' => 'https://storage.esb.co.id/cp/1.jpg', 'imageUrlText' => '1.jpg', 'refNum' => self::NUMBER],
            ],
            'locationID' => 2,
            'locationName' => 'Main store',
            'paymentID' => 1,
            'paymentName' => 'Cash',
            'phoneNumber' => '',
            'picFlagActive' => true,
            'picName' => 'Bu Sari',
            'picSupplierID' => 2,
            'projectID' => 0,
            'projectName' => '',
            'purchasePayments' => [
                [
                    'bankAccountName' => 'Petty cash',
                    'cashPurchaseNum' => self::NUMBER,
                    'currencyID' => 1,
                    'currencyName' => 'Rupiah',
                    'paymentDate' => '2026-03-20',
                    'paymentTotal' => 255000,
                    'purchasePaymentNum' => 'PP202603200001',
                    'statusID' => 3,
                    'statusName' => 'Authorized',
                ],
            ],
            'purchaseTypeID' => 1,
            'purchaseTypeName' => 'Regular',
            'rate' => 1,
            'releasePaymentNum' => '',
            'simplePurchaseCosts' => [
                ['ID' => 1, 'amount' => 15000, 'cashPurchaseNum' => self::NUMBER, 'coaNo' => '6100', 'description' => 'Parking', 'notes' => 'Market parking'],
            ],
            'simplePurchaseDetails' => [
                [
                    'ID' => 1,
                    'cashPurchaseNum' => self::NUMBER,
                    'categoryTypeID' => 1,
                    'discount' => 1000,
                    'discountPercent' => 2.5,
                    'dppValue' => 239000,
                    'flagLuxuryItem' => 0,
                    'lastPrice' => 12000,
                    'notes' => 'Fresh',
                    'price' => 12000,
                    'pricelistDate' => '',
                    'pricelistExpireDate' => '',
                    'pricelistPrice' => 0,
                    'pricelistSource' => 0,
                    'productCode' => 'Cabai_merah',
                    'productDetailID' => 702,
                    'productID' => 70,
                    'productName' => 'Cabai merah',
                    'qty' => 20,
                    'taxID' => 2,
                    'taxName' => 'PPN 11%',
                    'taxRate' => 11,
                    'total' => 239000,
                    'uomID' => 3,
                    'uomName' => 'Kg',
                    'vat' => 11,
                    'vatValue' => 26290,
                ],
            ],
            'simplePurchaseExpirations' => [],
            'statusID' => 1,
            'statusName' => 'New',
            'supplierDefaultPIC' => [
                'cellPhone' => '0812000000',
                'email' => 'sari@pasar.example',
                'flagDefault' => true,
                'flagSendingPOEmail' => false,
                'greetingID' => 1,
                'picName' => 'Bu Sari',
                'picSupplierID' => 2,
            ],
            'supplierID' => 14,
            'supplierInvoiceNum' => 'NOTA-0331',
            'supplierName' => 'Pasar Santa',
            'supplierPhone1' => '021-7200000',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $result
     * @return array<string, mixed>
     */
    public static function detailResponse(?array $result = null): array
    {
        return [
            'path' => 'https://stg7.esb.co.id/core-int/purchase/simple-purchase/'.self::NUMBER,
            'timestamp' => '2026-03-20 10:30:00',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => $result ?? self::detail(),
            'errors' => null,
        ];
    }

    /**
     * The envelope every write returns: result null.
     *
     * @return array<string, mixed>
     */
    public static function writeEnvelope(string $code = 'EC03100000', string $message = 'OK', string $status = 'ok'): array
    {
        return [
            'path' => 'https://stg7.esb.co.id/core-int/purchase/simple-purchase/'.self::NUMBER,
            'timestamp' => '2026-03-20 10:30:00',
            'status' => $status,
            'code' => $code,
            'message' => $message,
            'result' => null,
            'errors' => null,
        ];
    }
}
