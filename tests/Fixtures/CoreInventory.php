<?php

namespace Tests\Fixtures;

/**
 * ESB Core inventory document responses ([ESB-033]): list rows and details of
 * goods transfer requests, goods deliveries, goods receipts, item journals and
 * simple transfers. The vendor's examples are zero-valued; the values here
 * are realistic stand-ins, only the field names and types are the vendor's.
 */
final class CoreInventory
{
    use EsbEnvelope;

    public const GTR = 'GTR202603200001';

    public const GD = 'GD202603210001';

    public const GR = 'GR202603220001';

    public const IJ = 'IJ202603230001';

    public const ST = 'ST202603240001';

    // ------------------------------------------------- goods transfer request

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function gtrRow(array $overrides = []): array
    {
        return [
            'additionalInfo' => 'Restock outlet',
            'canDelete' => true,
            'canEdit' => true,
            'destinationBranch' => 'Outlet Senopati',
            'finishTransferRequest' => false,
            'originBranch' => 'Gudang Pusat',
            'purchaseRequestNum' => 'PR202603190001',
            'statusID' => 3,
            'statusName' => 'Authorized',
            'transferDate' => '2026-03-20',
            'transferNum' => self::GTR,
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function gtrDetail(array $overrides = []): array
    {
        return self::envelope('/inventory/goods-transfer-request/'.self::GTR, [
            'additionalInfo' => 'Restock outlet',
            'authorizedAt' => '2026-03-20 10:00:00',
            'authorizedBy' => 'budi',
            'authorizedByFullName' => 'Budi Santoso',
            'bypassGoodsDelivery' => false,
            'canDelete' => false,
            'canEdit' => false,
            'categoryTypeID' => 1,
            'categoryTypeName' => 'Inventory',
            'costCenter' => null,
            'createdAt' => '2026-03-20 08:30:00',
            'createdBy' => 'andi',
            'createdByFullName' => 'Andi',
            'destinationBranchID' => 3,
            'destinationBranchName' => 'Outlet Senopati',
            'editedAt' => '',
            'editedBy' => '',
            'editedByFullName' => null,
            'imageAttachments' => ['https://cdn.example.test/gtr.jpg'],
            'originBranchID' => 1,
            'originBranchName' => 'Gudang Pusat',
            'originLocationID' => 100,
            'originLocationName' => 'Gudang Utama',
            'project' => null,
            'purchaseRequestNum' => ['PR202603190001'],
            'statusID' => 3,
            'statusName' => 'Authorized',
            'transferDate' => '2026-03-20',
            'transferDetailAssets' => [],
            'transferDetails' => [[
                'availableQty' => 80,
                'pendingQty' => 5,
                'productCode' => 'BB-001',
                'productDetailID' => 501,
                'productID' => 50,
                'productName' => 'Beras Premium',
                'qty' => 20,
                'requestQty' => 25,
                'stockQty' => 85,
                'uomName' => 'KG',
            ]],
            'transferNum' => self::GTR,
            ...$overrides,
        ]);
    }

    // --------------------------------------------------------- goods delivery

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function gdRow(array $overrides = []): array
    {
        return [
            'additionalInfo' => '',
            'customerName' => null,
            'destBranchName' => 'Outlet Senopati',
            'destinationBranchID' => 3,
            'goodsDeliveryDate' => '2026-03-21',
            'goodsDeliveryNum' => self::GD,
            'originBranchID' => 1,
            'originBranchName' => 'Gudang Pusat',
            'referenceNumber' => self::GTR,
            'statusID' => 3,
            'statusName' => 'Authorized',
            'transType' => 'Transfer Request',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function gdDetail(array $overrides = []): array
    {
        return self::envelope('/inventory/goods-delivery/'.self::GD, [
            'additionalInfo' => 'Via truck B 1234',
            'authorizedBy' => 'budi',
            'authorizedDate' => '2026-03-21 11:00:00',
            'categoryTypeID' => 1,
            'costCenter' => null,
            'costCenterID' => null,
            'createdBy' => 'andi',
            'createdDate' => '2026-03-21 09:00:00',
            'customerAddress' => null,
            'customerInfo' => '',
            'customerPhone' => null,
            'destinationBranch' => 'Outlet Senopati',
            'destinationBranchID' => 3,
            'editedBy' => null,
            'editedDate' => null,
            'goodsDeliveryAssetDetails' => [],
            'goodsDeliveryDate' => '2026-03-21',
            'goodsDeliveryDetails' => [[
                'availableQty' => 80,
                'bookedQty' => 0,
                'categoryTypeID' => 1,
                'costCenter' => null,
                'costCenterID' => null,
                'deliveryQty' => 20,
                'expiredDate' => [],
                'hpp' => 12000.5,
                'hppTotal' => 240010,
                'notes' => 'Fragile',
                'productCode' => 'BB-001',
                'productDetailID' => 501,
                'productID' => 50,
                'productName' => 'Beras Premium',
                'project' => null,
                'projectID' => null,
                'receiptQty' => 0,
                'referenceNotes' => null,
                'requestQty' => 25,
                'stockQty' => 85,
                'uomID' => 4,
                'uomName' => 'KG',
            ]],
            'goodsDeliveryMultiAddress' => [],
            'goodsDeliveryNum' => self::GD,
            'goodsDeliveryURLAttachments' => [],
            'locationID' => 100,
            'locationName' => 'Gudang Utama',
            'locationTypeID' => 1,
            'locationTypeName' => 'Warehouse',
            'originBranch' => 'Gudang Pusat',
            'originBranchAddress' => 'Jl. Industri 1',
            'originBranchID' => 1,
            'originBranchPhone' => null,
            'project' => null,
            'projectID' => null,
            'refDate' => '2026-03-20',
            'referenceNumber' => self::GTR,
            'statusID' => 3,
            'statusName' => 'Authorized',
            'transType' => 'Transfer Request',
            'transferDate' => null,
            ...$overrides,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function gdOutstanding(): array
    {
        return self::envelope('/inventory/goods-delivery/get-details-outstanding-qty', [
            ['productDetailID' => 501, 'qty' => 5],
        ]);
    }

    // ---------------------------------------------------------- goods receipt

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function grRow(array $overrides = []): array
    {
        return [
            'additionalInfo' => null,
            'branchID' => 3,
            'branchName' => 'Outlet Senopati',
            'createdBy' => 'sari',
            'customerAdvancePayment' => [],
            'goodsReceiptDate' => '2026-03-22',
            'goodsReceiptNum' => self::GR,
            'linkGoodsReceiptNumEsbGoods' => null,
            'locationID' => 300,
            'refNum' => 'PO202603200001',
            'referenceModel' => ['additionalInfo' => '', 'refDate' => '2026-03-20', 'requiredDate' => '2026-03-22', 'sourceName' => 'CV Maju Jaya', 'statusID' => 3],
            'sourceName' => 'CV Maju Jaya',
            'statusID' => 5,
            'statusName' => 'Received',
            'transType' => 'Purchase Order',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function grDetail(array $overrides = []): array
    {
        return self::envelope('/inventory/goods-receipt/'.self::GR, [
            'additionalInfo' => 'Partial',
            'authorizedBy' => 'budi',
            'authorizedDate' => '2026-03-22 13:00:00',
            'branchID' => 3,
            'branchName' => 'Outlet Senopati',
            'costCenterID' => null,
            'costCenterName' => null,
            'createdBy' => 'sari',
            'createdDate' => '2026-03-22 12:00:00',
            'deliveryNum' => null,
            'editedBy' => null,
            'editedDate' => null,
            'expirations' => [],
            'flagImportDoc' => 0,
            'goodsDeliveryAssetModel' => [],
            'goodsReceiptAssetModel' => [],
            'goodsReceiptDate' => '2026-03-22',
            'goodsReceiptDetail' => [[
                'ID' => 1,
                'categoryID' => 3,
                'categoryName' => 'Bahan Baku',
                'categoryTypeID' => 1,
                'coaNo' => '1301',
                'deviationVal' => 0,
                'goodsReceiptNum' => self::GR,
                'notes' => null,
                'outstandingResult' => [],
                'productCode' => 'BB-001',
                'productDetailID' => 501,
                'productDetailQty' => 20,
                'productID' => 50,
                'productName' => 'Beras Premium',
                'qty' => 20,
                'uomID' => 4,
                'uomName' => 'KG',
            ]],
            'goodsReceiptNum' => self::GR,
            'imageAttachments' => [['ID' => 1, 'imageUrl' => 'https://cdn.example.test/gr.jpg', 'refNum' => self::GR], ['ID' => 0, 'imageUrl' => '', 'refNum' => '']],
            'linkGoodsDeliveryNum' => null,
            'linkGoodsReceiptNumEsbGoods' => null,
            'locationID' => 300,
            'locationName' => 'Gudang Outlet',
            'projectID' => null,
            'projectName' => null,
            'purchaseInvoice' => [[
                'branchID' => 3,
                'dueDay' => 30,
                'purchaseInvoiceDate' => '2026-03-23',
                'purchaseInvoiceDueDate' => '2026-04-22',
                'purchaseInvoiceNum' => 'PI202603230001',
                'refNum' => self::GR,
                'statusID' => 1,
                'statusName' => 'New',
                'supplierID' => 10,
                'supplierInvoiceNum' => null,
            ]],
            'refNum' => 'PO202603200001',
            'referenceModel' => ['additionalInfo' => '', 'refDate' => '2026-03-20', 'requiredDate' => '2026-03-22', 'sourceName' => 'CV Maju Jaya', 'statusID' => 3],
            'statusID' => 5,
            'statusName' => 'Received',
            'transType' => 'Purchase Order',
            ...$overrides,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function stockCheck(bool $used, string $path): array
    {
        return self::envelope($path, ['isStockHasBeenUsed' => $used]);
    }

    // ----------------------------------------------------------- item journal

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function ijRow(array $overrides = []): array
    {
        return [
            'additionalInfo' => 'Waste',
            'approval' => [],
            'approvalFlow' => [],
            'branchID' => 3,
            'branchName' => 'Outlet Senopati',
            'createdBy' => 'dewi',
            'itemJournalDate' => '2026-03-23',
            'itemJournalNum' => self::IJ,
            'locationID' => 300,
            'locationName' => 'Gudang Outlet',
            'statusID' => 38,
            'statusName' => 'Waiting For Approval',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function ijDetail(array $overrides = []): array
    {
        return self::envelope('/inventory/item-journal/'.self::IJ, [
            ...self::ijRow(),
            'authorizedBy' => null,
            'authorizedDate' => null,
            'authorizerFullName' => '',
            'createdDate' => '2026-03-23 16:00:00',
            'creatorFullName' => 'Dewi',
            'editedBy' => null,
            'editedDate' => null,
            'editorFullName' => '',
            'imageAttachments' => [],
            'itemJournalDetails' => [[
                'ID' => 1,
                'hpp' => 12000,
                'itemJournalNum' => self::IJ,
                'productDetailID' => 501,
                'productID' => 50,
                'productName' => 'Beras Premium',
                'purposeID' => 2,
                'purposeName' => 'Waste',
                'qty' => -3,
                'uomName' => 'KG',
            ]],
            'requestTemplateID' => null,
            'requestTemplateName' => '',
            ...$overrides,
        ]);
    }

    // -------------------------------------------------------- simple transfer

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function stRow(array $overrides = []): array
    {
        return [
            'additionalInfo' => '',
            'destinationBranchName' => 'Outlet Senopati',
            'destinationLocationID' => 301,
            'destinationLocationName' => 'Dapur',
            'hasAccessDestinationBranch' => true,
            'originBranchName' => 'Outlet Senopati',
            'originLocationName' => 'Gudang Outlet',
            'simpleTransferDate' => '2026-03-24',
            'simpleTransferNum' => self::ST,
            'statusID' => 1,
            'statusName' => 'New',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function stDetail(array $overrides = []): array
    {
        return self::envelope('/simple-transfer/'.self::ST, [
            'additionalInfo' => null,
            'authorizedBy' => null,
            'authorizedDate' => null,
            'costCenterID' => null,
            'costCenterName' => '',
            'createdBy' => 'dewi',
            'createdDate' => '2026-03-24 07:00:00',
            'destinationBranchID' => 3,
            'destinationBranchName' => 'Outlet Senopati',
            'destinationLocationID' => 301,
            'destinationLocationName' => 'Dapur',
            'editedBy' => null,
            'editedDate' => null,
            'hasAccessDestinationBranch' => true,
            'imageAttachments' => [],
            'originBranchID' => 3,
            'originBranchName' => 'Outlet Senopati',
            'originLocationID' => 300,
            'originLocationName' => 'Gudang Outlet',
            'projectID' => null,
            'projectName' => '',
            'simpleTransferAssets' => [],
            'simpleTransferDate' => '2026-03-24',
            'simpleTransferDetails' => [[
                'assetIDs' => [],
                'availableQty' => 40,
                'isAsset' => false,
                'productDetailID' => 501,
                'productName' => 'Beras Premium',
                'qty' => 10,
                'stockQty' => 40,
                'uomName' => 'KG',
            ]],
            'simpleTransferNum' => self::ST,
            'statusID' => 1,
            ...$overrides,
        ]);
    }

    // ------------------------------------------------------------------ lists

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public static function listResponse(string $path, array $rows, int $count = 1, int $page = 1): array
    {
        return self::paginated($path, $rows, $count, $page);
    }

    // ----------------------------------------------------------------- writes

    /**
     * The answer to an authorize / reject / delete / finish / reopen: the
     * vendor documents result null for all of them (a goods receipt delete
     * echoes its number).
     *
     * @return array<string, mixed>
     */
    public static function writeEnvelope(string $code = 'EC03100000', string $message = 'OK', string $status = 'ok', mixed $result = null): array
    {
        return [
            ...self::envelope('/inventory', $result),
            'status' => $status,
            'code' => $code,
            'message' => $message,
        ];
    }
}
