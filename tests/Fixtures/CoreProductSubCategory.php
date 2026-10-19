<?php

namespace Tests\Fixtures;

/**
 * ESB Core product sub-categories ([ESB-022]): GET /product/sub-category and
 * GET /product/sub-category/{subCategoryID}. Realistic stand-ins for the
 * vendor's zero-valued examples; field names and types are the vendor's.
 */
final class CoreProductSubCategory
{
    use EsbEnvelope;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function row(array $overrides = []): array
    {
        return [
            'subCategoryID' => 7,
            'subCategoryName' => 'Beras',
            'deadStockThreshold' => 90,
            'notes' => '',
            'flagActive' => 1,
            ...$overrides,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function listResponse(int $count = 1, int $page = 1): array
    {
        return self::paginated('/product/sub-category', [self::row()], $count, $page);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function detailResponse(array $overrides = []): array
    {
        return self::envelope('/product/sub-category/7', [
            'subCategoryID' => 7,
            'subCategoryName' => 'Beras',
            'deadStockThreshold' => 90,
            'notes' => 'Semua jenis beras',
            'flagActive' => 1,
            'createdBy' => 'admin',
            'createdDate' => '2026-01-10 08:00:00',
            'editedBy' => '',
            'editedDate' => '',
            ...$overrides,
        ]);
    }
}
