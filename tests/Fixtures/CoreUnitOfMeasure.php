<?php

namespace Tests\Fixtures;

/**
 * ESB Core units ([ESB-020]): GET /units and GET /units/{uomID}. Realistic
 * stand-ins for the vendor's zero-valued examples; field names and types are
 * the vendor's.
 */
final class CoreUnitOfMeasure
{
    use EsbEnvelope;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function row(array $overrides = []): array
    {
        return [
            'uomID' => 4,
            'uomName' => 'KG',
            'metricID' => 2,
            'metricName' => 'Weight',
            'notes' => 'Kilogram',
            'refTaxCode' => 'KGM',
            'refTaxName' => 'Kilogram',
            'flagActive' => 1,
            ...$overrides,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function listResponse(int $count = 1, int $page = 1): array
    {
        return self::paginated('/units', [self::row()], $count, $page);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function detailResponse(array $overrides = []): array
    {
        return self::envelope('/units/4', [
            'uomID' => 4,
            'uomName' => 'KG',
            'metricID' => 2,
            'metricName' => 'Weight',
            'notes' => 'Kilogram',
            'refTaxID' => 9,
            'flagActive' => 1,
            'createdBy' => 'admin',
            'createdDate' => '2026-01-10 08:00:00',
            'editedBy' => '',
            'editedDate' => '',
            ...$overrides,
        ]);
    }
}
