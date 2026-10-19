<?php

namespace Tests\Fixtures;

/**
 * ESB Core Master Document Template responses ([ESB-016]): GET
 * /document-template and GET /document-template/{requestTemplateID}, in the
 * shape of the vendor's OpenAPI schema. The vendor's examples are
 * zero-valued, so the values here are realistic stand-ins; only the field
 * names and types are the vendor's.
 */
final class CoreDocumentTemplate
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function row(array $overrides = []): array
    {
        return [
            'requestTemplateID' => 12,
            'requestTemplateName' => 'Weekly Kitchen Order',
            'requestTemplateTypeNames' => 'Purchase Request',
            'branchNames' => 'Kemang, Senopati',
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
            'path' => 'https://stg7.esb.co.id/core-int/document-template',
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
    public static function detailLine(array $overrides = []): array
    {
        return [
            'ID' => 301,
            'availableQty' => 8.5,
            'categoryName' => 'Dry Goods',
            'categoryTypeID' => 1,
            'flagActive' => 1,
            'lastHpp' => 14500.25,
            'mode' => null,
            'productCode' => 'RM-0001',
            'productDetailID' => 5001,
            'productID' => 900,
            'productName' => 'Beras Premium',
            'purposeID' => 7,
            'requestProcessID' => 1,
            'requestTemplateDetailName' => 'Beras',
            'requestTemplateID' => 12,
            'stockQty' => 10,
            'uomName' => 'KG',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function detail(array $overrides = []): array
    {
        return [
            'requestTemplateID' => 12,
            'requestTemplateName' => 'Weekly Kitchen Order',
            'requestTemplateTypeID' => 1,
            'flagActive' => 1,
            'flagAutoTransfer' => 0,
            'allowZeroPricelist' => null,
            'branchIDs' => [3, 4, 0, 'x'],
            'costCenterIDs' => [21],
            'currencyID' => null,
            'destinationBranchID' => null,
            'supplierID' => null,
            'requestTemplateDetails' => [
                self::detailLine(),
                self::detailLine([
                    'ID' => 302,
                    'productCode' => 'RM-0002',
                    'productDetailID' => 5002,
                    'productName' => 'Minyak Goreng',
                    'requestTemplateDetailName' => '',
                    'purposeID' => null,
                    'lastHpp' => null,
                    'uomName' => 'LTR',
                    'flagActive' => 0,
                ]),
            ],
            'createdBy' => 'admin',
            'createdDate' => '2026-01-10 08:00:00',
            'editedBy' => null,
            'editedDate' => null,
            'syncDate' => null,
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
            'path' => 'https://stg7.esb.co.id/core-int/document-template/12',
            'timestamp' => '2026-01-15 10:30:00',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => $detail ?? self::detail(),
            'errors' => null,
        ];
    }
}
