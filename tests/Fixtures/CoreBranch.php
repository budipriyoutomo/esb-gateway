<?php

namespace Tests\Fixtures;

/**
 * ESB Core Branch responses ([ESB-051]): GET /branch and
 * GET /branch/{branchID}, in the shape of the vendor's OpenAPI schema. The
 * vendor's examples are zero-valued, so the values here are realistic
 * stand-ins; only the field names and types are the vendor's.
 */
final class CoreBranch
{
    /**
     * A GET /branch row: id, code and name only (BrowseBranchResponse).
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function row(array $overrides = []): array
    {
        return [
            'branchID' => 12,
            'branchCode' => 'OUT1',
            'branchName' => 'Maharasa Kemang',
            ...$overrides,
        ];
    }

    /**
     * GET /branch is not paginated: result is the whole list.
     *
     * @param  list<array<string, mixed>>|null  $rows
     * @return array<string, mixed>
     */
    public static function listResponse(?array $rows = null): array
    {
        return [
            'path' => 'https://stg7.esb.co.id/core-int/branch',
            'timestamp' => '2026-01-15 10:30:00',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => $rows ?? [self::row()],
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
            'additionalTaxName' => 'Service',
            'additionalTaxValue' => '5',
            'address' => 'Jl. Kemang Raya 10',
            'autoEmailUsername' => '',
            'branchCode' => 'OUT1',
            'branchID' => 12,
            'branchName' => 'Maharasa Kemang',
            'branchNameESBOrder' => 'Kemang',
            'branchTypeID' => 2,
            'brandID' => 0,
            'cashierCount' => 2,
            'closeDate' => '',
            'createdBy' => 'admin',
            'createdDate' => '2026-01-10 08:00:00',
            'districtID' => 0,
            'editedBy' => '',
            'editedDate' => '',
            'extBranchCode' => 'KMG',
            'flagActive' => 1,
            'flagFooterImageOriginalSize' => 0,
            'flagHeaderImageOriginalSize' => 0,
            'flagOtherTaxVat' => 0,
            'image' => 'https://cdn.example.test/header.png',
            'imageFooter' => '',
            'kioskVersion' => '',
            'latitude' => '-6.2607',
            'longitude' => '106.8137',
            'menuTemplateID' => 0,
            'odsVersion' => '',
            'openDate' => '2025-03-01',
            'orderCount' => 3,
            'phone' => '021-555-0101',
            'posModeID' => 0,
            'posOtherTaxCalculationID' => 0,
            'posTaxCalculationID' => 0,
            'posVersion' => '4.2.0',
            'postalCode' => '12730',
            'printingCheckerFooter' => '',
            'printingFooter' => 'Terima kasih',
            'printingHeader' => 'Maharasa',
            'selfOrderBanner' => '',
            'serverOnlyCount' => 0,
            'subDistrictID' => 0,
            'tabCount' => 1,
            'tableSideVersion' => '',
            'taxName' => 'PB1',
            'taxValue' => 10,
            'timezoneID' => 1,
            'vatName' => '',
            'vatSubject' => 0,
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
            'path' => 'https://stg7.esb.co.id/core-int/branch/12',
            'timestamp' => '2026-01-15 10:30:00',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => $detail ?? self::detail(),
            'errors' => null,
        ];
    }
}
