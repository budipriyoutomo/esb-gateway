<?php

namespace Tests\Fixtures;

/**
 * ESB Core accounting responses: the general journal ([ESB-041]), the
 * general ledger ([ESB-042]) and memorial journals ([ESB-043]). The vendor's
 * examples are zero-valued; the values here are realistic stand-ins, only
 * the field names and types are the vendor's.
 */
final class CoreAccounting
{
    use EsbEnvelope;

    public const JOURNAL_HEAD_ID = 88123;

    public const MJ = 'MJ202603260001';

    // ------------------------------------------------------- general journal

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function journalRow(array $overrides = []): array
    {
        return [
            'journalDate' => '2026-03-25',
            'journalHeadID' => self::JOURNAL_HEAD_ID,
            'notes' => 'Sewa ruko Maret',
            'refNum' => 'DB202603250001',
            'transactionType' => 'Disbursement',
            'typeHasAccessView' => true,
            ...$overrides,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public static function journalList(array $rows, int $count = 1, int $page = 1): array
    {
        return self::paginated('/accounting/general-journal/search', $rows, $count, $page);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function journalLine(array $overrides = []): array
    {
        return [
            'branchID' => 3,
            'branchName' => 'Outlet Senopati',
            'coaDesc' => 'Beban Sewa',
            'coaNo' => '6101',
            'costCenterID' => 7,
            'costCenterName' => 'Operasional',
            'crAmount' => 0,
            'currencyID' => 1,
            'currencyName' => 'Rupiah',
            'drAmount' => 15000000.5,
            'journalDetailID' => 990001,
            'journalHeadID' => self::JOURNAL_HEAD_ID,
            'notes' => 'Sewa Maret',
            'projectID' => 0,
            'projectName' => '',
            'rate' => 1,
            ...$overrides,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public static function journalLines(array $rows, int $count = 2, int $page = 1): array
    {
        return self::paginated('/accounting/general-journal/'.self::JOURNAL_HEAD_ID.'/detail', $rows, $count, $page);
    }

    // -------------------------------------------------------- general ledger

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function ledgerEntry(array $overrides = []): array
    {
        return [
            'branchID' => 3,
            'branchName' => 'Outlet Senopati',
            'coaDescription' => 'Kas Outlet',
            'coaNumber' => '1101',
            'costCenterName' => '',
            'createdDate' => '2026-03-25 09:00:00',
            'creditAmount' => 250000,
            'debitAmount' => 0,
            'generalLedgerInfo' => 'Listrik Maret',
            'journalDate' => '2026-03-25',
            'notes' => 'Listrik',
            'projectName' => null,
            'referenceNumber' => 'DB202603250002',
            'transactionType' => 'Disbursement',
            ...$overrides,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     * @return array<string, mixed>
     */
    public static function ledger(array $entries): array
    {
        return self::envelope('/accounting/general-ledger', $entries);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function summaryRow(array $overrides = []): array
    {
        return [
            'accountDescription' => 'Kas Outlet',
            'accountNo' => '1101',
            'branchCode' => 'SNP',
            'branchID' => 3,
            'branchName' => 'Outlet Senopati',
            'creditAmount' => 250000,
            'debitAmount' => 1000000,
            'journalDate' => '2026-03-25',
            ...$overrides,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public static function summary(array $rows, int $count = 1, int $page = 1): array
    {
        return self::paginated('/accounting/general-ledger/summary', $rows, $count, $page);
    }

    // ------------------------------------------------------ memorial journal

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function mjRow(array $overrides = []): array
    {
        return [
            'additionalInfo' => 'Koreksi biaya sewa',
            'approval' => true,
            'createdBy' => 'andi',
            'memorialJournalDate' => '2026-03-26',
            'memorialJournalNum' => self::MJ,
            'statusID' => 1,
            'statusName' => 'New',
            ...$overrides,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public static function mjList(array $rows, int $count = 1, int $page = 1): array
    {
        return self::paginated('/accounting/memorial-journal', $rows, $count, $page);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function mjDetailResult(array $overrides = []): array
    {
        return [
            'additionalInfo' => 'Koreksi biaya sewa',
            'approval' => true,
            'authorizedBy' => '',
            'authorizedDate' => '',
            'authorizer' => ['fullName' => null, 'username' => null],
            'createdBy' => 'andi',
            'createdDate' => '2026-03-26 08:00:00',
            'creator' => ['fullName' => 'Andi Pratama', 'username' => 'andi'],
            'editedBy' => '',
            'editedDate' => '',
            'editor' => ['fullName' => null, 'username' => null],
            'image' => [
                ['ID' => 5, 'imageUrl' => 'https://cdn.example.test/mj.jpg', 'imageUrlText' => 'mj.jpg', 'refNum' => self::MJ],
            ],
            'memorialJournalDate' => '2026-03-26',
            'memorialJournalDetails' => [
                [
                    'ID' => 71,
                    'branchID' => 3,
                    'branchName' => 'Outlet Senopati',
                    'coaNo' => '6101',
                    'costCenterID' => 0,
                    'costCenterName' => null,
                    'crAmount' => 0,
                    'currencyID' => 1,
                    'currencyName' => 'Rupiah',
                    'description' => 'Beban Sewa',
                    'drAmount' => 500000,
                    'memorialJournalNum' => self::MJ,
                    'notes' => 'Koreksi',
                    'projectID' => 0,
                    'projectName' => null,
                    'rate' => 1,
                ],
                [
                    'ID' => 72,
                    'branchID' => 3,
                    'branchName' => 'Outlet Senopati',
                    'coaNo' => '2101',
                    'costCenterID' => 0,
                    'costCenterName' => null,
                    'crAmount' => 500000,
                    'currencyID' => 1,
                    'currencyName' => 'Rupiah',
                    'description' => 'Hutang Usaha',
                    'drAmount' => 0,
                    'memorialJournalNum' => self::MJ,
                    'notes' => null,
                    'projectID' => 0,
                    'projectName' => null,
                    'rate' => 1,
                ],
            ],
            'memorialJournalNum' => self::MJ,
            'statusID' => 1,
            'statusName' => 'New',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function mjDetail(array $overrides = []): array
    {
        return self::envelope('/accounting/memorial-journal/'.self::MJ, self::mjDetailResult($overrides));
    }

    /**
     * @return array<string, mixed>
     */
    public static function failure(string $code = 'EC03100003', string $message = 'Validation Error'): array
    {
        return [
            ...self::envelope('/accounting', null),
            'status' => 'fail',
            'code' => $code,
            'message' => $message,
        ];
    }
}
