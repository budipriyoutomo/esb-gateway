<?php

namespace Tests\Fixtures;

/**
 * ESB Core disbursement responses ([ESB-037]). The vendor's examples are
 * zero-valued; the values here are realistic stand-ins, only the field names
 * and types are the vendor's.
 */
final class CoreDisbursement
{
    use EsbEnvelope;

    public const NUMBER = 'DB202603250001';

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function row(array $overrides = []): array
    {
        return [
            'additionalInfo' => 'Sewa ruko Maret',
            'branchID' => 3,
            'branchName' => 'Outlet Senopati',
            'disbursementDate' => '2026-03-25',
            'disbursementNum' => self::NUMBER,
            'grandTotal' => 15000000.5,
            'hasApproval' => true,
            'invoiceDueDate' => '2026-04-24',
            'overDue' => 0,
            'paymentMethodName' => 'Credit',
            'ref' => 'INV-RUKO-03',
            'statusID' => 1,
            'statusName' => 'New',
            'supplierID' => 14,
            'supplierName' => 'PT Properti Jaya',
            ...$overrides,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public static function listResponse(array $rows, int $count = 1, int $page = 1): array
    {
        return self::paginated('/finance/disbursement', $rows, $count, $page);
    }

    /**
     * A credit disbursement with a cost center, a project and a bank account.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function detailResult(array $overrides = []): array
    {
        return [
            'additionalInformation' => 'Sewa ruko Maret',
            'authorizedBy' => '',
            'authorizedDate' => '',
            'bankAccountName' => 'PT Properti Jaya',
            'bankAccountNumber' => '1234567890123',
            'bankName' => 'BCA',
            'branchID' => 3,
            'branchName' => 'Outlet Senopati',
            'costCenterID' => 7,
            'costCenterName' => 'Operasional',
            'createdBy' => 'andi',
            'createdDate' => '2026-03-25 09:00:00',
            'creditTerm' => 30,
            'currencyID' => 1,
            'currencyName' => 'Rupiah',
            'currencySign' => 'Rp',
            'disbursementCoaNo' => '',
            'disbursementDate' => '2026-03-25',
            'disbursementDetails' => [
                [
                    'amount' => 15000000.5,
                    'branchID' => 3,
                    'branchName' => 'Outlet Senopati',
                    'coaDescription' => 'Beban Sewa',
                    'coaNo' => '6101',
                    'description' => 'Sewa Maret',
                ],
            ],
            'disbursementNum' => self::NUMBER,
            'editedBy' => '',
            'editedDate' => '',
            'grandTotal' => 15000000.5,
            'imageAttachments' => [],
            'invoiceDueDate' => '2026-04-24',
            'paymentID' => 2,
            'paymentName' => 'Credit',
            'projectID' => 4,
            'projectName' => 'Renovasi',
            'purchasePaymentDetails' => [
                [
                    'coaNo' => '1101',
                    'currencyName' => 'Rupiah',
                    'paymentTotal' => 5000000,
                    'purchasePaymentDate' => '2026-03-28',
                    'purchasePaymentNum' => 'PP202603280001',
                    'status' => 'Authorized',
                ],
            ],
            'rate' => 1,
            'ref' => 'INV-RUKO-03',
            'releasePaymentCode' => '',
            'statusID' => 1,
            'supplierBankID' => 9,
            'supplierID' => 14,
            'supplierName' => 'PT Properti Jaya',
            'supplierSettlementDetails' => [],
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function detail(array $overrides = []): array
    {
        return self::envelope('/finance/disbursement/'.self::NUMBER, self::detailResult($overrides));
    }

    /**
     * POST /finance/disbursement/import/form: the parsed rows.
     *
     * @return array<string, mixed>
     */
    public static function formLines(): array
    {
        return self::envelope('/finance/disbursement/import/form', [
            ['amount' => 250000, 'branchID' => 3, 'branchName' => 'Outlet Senopati', 'coaDescription' => 'Beban Listrik', 'coaNo' => '6102', 'description' => 'Listrik Maret'],
            ['amount' => 0, 'branchID' => 0, 'branchName' => '', 'coaDescription' => '', 'coaNo' => '', 'description' => ''],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function writeEnvelope(string $code = 'EC03100000', string $message = 'OK', string $status = 'ok', mixed $result = null): array
    {
        return [
            ...self::envelope('/finance/disbursement', $result),
            'status' => $status,
            'code' => $code,
            'message' => $message,
        ];
    }
}
