<?php

namespace Tests\Fixtures;

/**
 * ESB Core supplier categories ([ESB-023]): GET /supplier/category/list and
 * GET /supplier/category/{supplierCategoryID}. Realistic stand-ins for the
 * vendor's zero-valued examples; field names and types are the vendor's.
 */
final class CoreSupplierCategory
{
    use EsbEnvelope;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function row(array $overrides = []): array
    {
        return [
            'supplierCategoryID' => 5,
            'supplierCategoryName' => 'Bahan Pokok',
            'flagActive' => 1,
            ...$overrides,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function listResponse(int $count = 1, int $page = 1): array
    {
        return self::paginated('/supplier/category/list', [self::row()], $count, $page);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function detailResponse(array $overrides = []): array
    {
        return self::envelope('/supplier/category/5', [
            'supplierCategoryID' => 5,
            'supplierCategoryName' => 'Bahan Pokok',
            'flagActive' => 1,
            'createdBy' => 'admin',
            'createdDate' => '2026-01-10 08:00:00',
            'editedBy' => '',
            'editedDate' => '',
            ...$overrides,
        ]);
    }
}
