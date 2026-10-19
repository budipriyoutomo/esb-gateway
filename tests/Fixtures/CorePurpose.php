<?php

namespace Tests\Fixtures;

/**
 * ESB Core Purpose responses ([ESB-015]): GET /purpose and
 * GET /purpose/{purposeID}, in the shape of the vendor's OpenAPI schema. The
 * vendor's examples are zero-valued, so the values here are realistic
 * stand-ins; only the field names and types are the vendor's.
 */
final class CorePurpose
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function row(array $overrides = []): array
    {
        return [
            'purposeID' => 7,
            'purposeName' => 'Entertainment',
            'purposeCoaNo' => '6105',
            'purposeAccount' => 'Biaya Entertainment',
            'purposeAppliedTo' => ['Stock Usage', 'Petty Cash'],
            'flagActive' => true,
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
            'path' => 'https://stg7.esb.co.id/core-int/purpose',
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
            'purposeID' => 7,
            'purposeName' => 'Entertainment',
            'purposeCoaNo' => '6105',
            'flagActive' => true,
            'transactionTypeIDs' => [3, 5, 0, 'x'],
            'createdBy' => 'admin',
            'createdDate' => '2026-01-10 08:00:00',
            'editedBy' => '',
            'editedDate' => '',
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
            'path' => 'https://stg7.esb.co.id/core-int/purpose/7',
            'timestamp' => '2026-01-15 10:30:00',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => $detail ?? self::detail(),
            'errors' => null,
        ];
    }
}
