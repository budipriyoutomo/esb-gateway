<?php

namespace Tests\Fixtures;

/**
 * An ESB Core GET /menu/browse response, in the shape of the vendor's OpenAPI
 * example ([ESB-012]). The vendor's example is zero-valued, so the values here
 * are realistic stand-ins; only the field names and types are the vendor's.
 */
final class MenuBrowse
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function row(array $overrides = []): array
    {
        return [
            'menuID' => 5564,
            'menuCode' => 'PS01',
            'menuName' => 'Paket Separate',
            'menuShortName' => 'Paket Sep',
            'categoryID' => 16,
            'categoryDesc' => 'Food',
            'categoryDetailID' => 34,
            'categoryDetailDesc' => 'Snack',
            'notes' => '',
            ...$overrides,
        ];
    }

    /**
     * @param  list<array<string, mixed>>|null  $rows
     * @return array<string, mixed>
     */
    public static function response(?array $rows = null, int $count = 1, int $page = 1): array
    {
        return [
            'path' => 'https://stg7.esb.co.id/core-int/menu/browse',
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
}
