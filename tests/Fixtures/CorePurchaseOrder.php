<?php

namespace Tests\Fixtures;

/**
 * ESB Core Purchase Order responses ([ESB-017]): GET /purchase/purchase-order
 * and GET /purchase/purchase-order/{purchaseNum}, in the shape of the vendor's
 * OpenAPI schema. The vendor's examples are zero-valued, so the values here
 * are realistic stand-ins; only the field names and types are the vendor's.
 */
final class CorePurchaseOrder
{
    public const NUMBER = 'PO202603200001';

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function row(array $overrides = []): array
    {
        return [
            'access' => ['canDelete' => true, 'canEdit' => true, 'unclose' => false],
            'additionalInfo' => 'Weekly water order',
            'approval' => [],
            'approvalFlow' => [],
            'branchID' => 3,
            'branchName' => 'Outlet Senopati',
            'createdBy' => 'andi',
            'currencyID' => 1,
            'currencySign' => 'Rp',
            'emailSupplier' => 'sales@sumber-air.example',
            'emailedBy' => '',
            'emailedDate' => '',
            'linkPurchaseNumEsbGoods' => '',
            'mapSupplierLink' => ['companyCustomerID' => 0, 'companyID' => 0, 'companyName' => '', 'supplierID' => 0],
            'printedBy' => '',
            'printedDate' => '',
            'purchaseDate' => '2026-03-20',
            'purchaseNum' => self::NUMBER,
            'purchaseRequestNums' => 'PR202603180002',
            'purchaseTotal' => 1110000.5,
            'purchaseTypeID' => 1,
            'requiredDate' => '2026-03-25',
            'statusID' => 1,
            'statusName' => 'New',
            'supplierID' => 14,
            'supplierName' => 'PT Sumber Air',
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
            'path' => 'https://stg7.esb.co.id/core-int/purchase/purchase-order',
            'timestamp' => '2026-03-20 10:30:00',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => [
                'count' => $count,
                'data' => $rows ?? [self::row()],
                'limit' => 20,
                'next' => 'https://stg7.esb.co.id/core-int/purchase/purchase-order?page=2&limit=20',
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
            'access' => ['canDelete' => false, 'canEdit' => true, 'unclose' => false],
            'additionalInfo' => 'Weekly water order',
            'address' => 'Jl. Senopati 10',
            'approval' => [],
            'approvalFlow' => [],
            'authorizedBy' => '',
            'authorizedDate' => '',
            'authorizer' => ['fullName' => '', 'username' => ''],
            'branchID' => 3,
            'branchName' => 'Outlet Senopati',
            'costCenterID' => 5,
            'costCenterName' => 'Bar',
            'createdBy' => 'andi',
            'createdDate' => '2026-03-20 09:15:00',
            'creator' => ['fullName' => 'Andi Wijaya', 'username' => 'andi'],
            'currencyID' => 1,
            'currencyName' => 'Rupiah',
            'currencySign' => 'Rp',
            'dueDay' => 30,
            'editedBy' => '',
            'editedDate' => '',
            'editor' => ['fullName' => '', 'username' => ''],
            'emailedBy' => '',
            'emailedDate' => '',
            'flagImportDoc' => 0,
            'footNote' => 'Deliver before 10:00',
            'goodsReceiptHead' => [
                ['goodsReceiptDate' => '2026-03-25', 'goodsReceiptNum' => 'GR202603250002', 'statusID' => 3, 'statusName' => 'Authorized'],
            ],
            'images' => [
                ['ID' => 1, 'imageUrl' => 'https://storage.esb.co.id/po/1.jpg', 'imageUrlText' => '1.jpg', 'refNum' => self::NUMBER],
            ],
            'linkProductSalesNum' => '',
            'linkPurchaseNumEsbGoods' => '',
            'phoneNumber' => '',
            'picFlagActive' => true,
            'picName' => 'Budi',
            'picSupplierID' => 2,
            'printedBy' => '',
            'printedDate' => '',
            'projectID' => 0,
            'projectName' => '',
            'purchaseDate' => '2026-03-20',
            'purchaseDetails' => [
                [
                    'ID' => 1,
                    'categoryTypeID' => 1,
                    'convertionQty' => 1,
                    'detailQty' => 200,
                    'deviationVal' => 0,
                    'discount' => 1000,
                    'discountPercent' => 2.5,
                    'dppValue' => 999000,
                    'flagLuxuryItem' => 0,
                    'fullReceipt' => 0,
                    'lastPrice' => 5000,
                    'lastPriceDate' => '2026-03-01',
                    'notes' => 'Cold storage',
                    'price' => 5000,
                    'pricelistDate' => '',
                    'pricelistExpireDate' => '',
                    'pricelistPrice' => 0,
                    'pricelistSource' => 0,
                    'productCode' => 'Air_mineral',
                    'productDetailID' => 901,
                    'productID' => 90,
                    'productName' => 'Air mineral',
                    'purchaseNum' => self::NUMBER,
                    'qty' => 200,
                    'requestQty' => 180,
                    'stockQty' => 200,
                    'sumRequestQty' => 180,
                    'total' => 999000,
                    'uomID' => 4,
                    'uomName' => 'Bottle',
                    'vat' => 11,
                    'vatValue' => 109890,
                ],
            ],
            'purchaseInvoiceHead' => [
                [
                    'invoiceTotal' => 1110000,
                    'purchaseInvoiceDate' => '2026-03-26',
                    'purchaseInvoiceNum' => 'PI202603260005',
                    'statusID' => 1,
                    'statusName' => 'New',
                    'supplierInvoiceNum' => 'INV/SUP/0331',
                ],
            ],
            'purchaseNum' => self::NUMBER,
            'purchaseRequestNums' => 'PR202603180002',
            'purchaseTotal' => 1110000,
            'purchaseTypeID' => 1,
            'purchaseTypeName' => 'Regular',
            'rate' => 1,
            'requestRequiredDateTo' => '',
            'requiredDate' => '2026-03-25',
            'statusID' => 1,
            'statusName' => 'New',
            'supplierAddress' => 'Jl. Industri 4, Bekasi',
            'supplierBranchID' => 0,
            'supplierBranchName' => '',
            'supplierDefaultPIC' => [
                'cellPhone' => '0812000000',
                'email' => 'budi@sumber-air.example',
                'flagDefault' => true,
                'flagSendingPOEmail' => true,
                'greetingID' => 1,
                'picName' => 'Budi',
                'picSupplierID' => 2,
            ],
            'supplierDueDate' => 30,
            'supplierID' => 14,
            'supplierName' => 'PT Sumber Air',
            'supplierPhone1' => '021-8800000',
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
            'path' => 'https://stg7.esb.co.id/core-int/purchase/purchase-order/'.self::NUMBER,
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
            'path' => 'https://stg7.esb.co.id/core-int/purchase/purchase-order/'.self::NUMBER,
            'timestamp' => '2026-03-20 10:30:00',
            'status' => $status,
            'code' => $code,
            'message' => $message,
            'result' => null,
            'errors' => null,
        ];
    }
}
