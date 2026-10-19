<?php

namespace Tests\Fixtures;

/**
 * ESB Core chart of accounts ([ESB-025]): GET /accounting/coa and
 * GET /accounting/coa/{coaNo}. Realistic stand-ins for the vendor's
 * zero-valued examples; field names and types are the vendor's.
 */
final class CoreCoaAccount
{
    use EsbEnvelope;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function row(string $coaNo, string $description, int $level, int $order, array $overrides = []): array
    {
        return [
            'coaNo' => $coaNo,
            'description' => $description,
            'coaLevel' => $level,
            'orderList' => $order,
            'branchID' => 0,
            'currency' => 'Rp',
            'flagModule' => 0,
            'allowBudget' => 0,
            'flagActive' => 1,
            'additionalInfo' => '',
            'releasePaymentCode' => null,
            'createdBy' => 'admin',
            'createdDate' => '2026-01-10 08:00:00',
            'editedBy' => null,
            'editedDate' => null,
            'mapCoa' => [],
            ...$overrides,
        ];
    }

    /**
     * Deliberately out of order: the gateway sorts by orderList.
     *
     * @return list<array<string, mixed>>
     */
    public static function chart(): array
    {
        return [
            self::row('1101', 'Kas', 4, 3, ['flagModule' => 1]),
            self::row('1', 'Aset', 1, 1),
            self::row('11', 'Aset Lancar', 2, 2),
            self::row('6101', 'Biaya Listrik', 4, 5, ['allowBudget' => 1]),
            self::row('6', 'Beban', 1, 4),
        ];
    }

    /**
     * @param  list<array<string, mixed>>|null  $rows
     * @return array<string, mixed>
     */
    public static function listResponse(?array $rows = null): array
    {
        return self::envelope('/accounting/coa', $rows ?? self::chart());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function detailResponse(array $overrides = []): array
    {
        return self::envelope('/accounting/coa/1101', self::row('1101', 'Kas', 4, 3, [
            'flagModule' => 1,
            'mapCoa' => [
                ['ID' => 31, 'coaNo' => '1101', 'key' => 'CASH', 'mapCoaTypeID' => 2, 'currencyID' => 1],
            ],
            ...$overrides,
        ]));
    }
}
