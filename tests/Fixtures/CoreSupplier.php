<?php

namespace Tests\Fixtures;

/**
 * ESB Core suppliers ([ESB-024]): GET /supplier and GET /supplier/{supplierID}.
 * Realistic stand-ins for the vendor's zero-valued examples; field names and
 * types are the vendor's.
 */
final class CoreSupplier
{
    use EsbEnvelope;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function row(array $overrides = []): array
    {
        return [
            'supplierID' => 10,
            'supplierCode' => 'SUP-001',
            'supplierName' => 'PT Sumber Pangan',
            'supplierCategoryID' => 5,
            'category' => 'Bahan Pokok',
            'address' => 'Jl. Pasar Baru 12',
            'contactPerson' => 'Andi',
            'cellPhone' => '081234567890',
            'dueDate' => 30,
            'vatSubject' => true,
            'lockVAT' => false,
            'flagActive' => true,
            'linkSupplierCodeEsbGoods' => null,
            'supplierNameEsbGoods' => null,
            ...$overrides,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function listResponse(int $count = 1, int $page = 1): array
    {
        return self::paginated('/supplier', [self::row()], $count, $page);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function detailResponse(array $overrides = []): array
    {
        return self::envelope('/supplier/10', [
            'supplierID' => 10,
            'supplierCode' => 'SUP-001',
            'supplierName' => 'PT Sumber Pangan',
            'supplierCategoryID' => 5,
            'supplierCategoryName' => 'Bahan Pokok',
            'address' => 'Jl. Pasar Baru 12',
            'city' => 'Jakarta',
            'state' => 'DKI Jakarta',
            'country' => 'Indonesia',
            'zipCode' => '10710',
            'phone1' => '021-555-0101',
            'phone2' => '',
            'fax' => '',
            'npwp' => '01.234.567.8-901.000',
            'notes' => '',
            'dueDate' => 30,
            'payableCoaNo' => '2101',
            'advancePurchaseCoaNo' => '',
            'paymentID' => 2,
            'vatSubject' => true,
            'lockVAT' => false,
            'flagInvoiceOnReceipt' => false,
            'isAnyTransaction' => true,
            'flagActive' => true,
            'supplierPic' => [
                [
                    'picSupplierID' => 71,
                    'picName' => 'Andi',
                    'cellPhone' => '081234567890',
                    'email' => 'andi@sumberpangan.test',
                    'flagDefault' => true,
                    'flagSendingPOEmail' => true,
                    'greetingID' => 1,
                ],
            ],
            'supplierBank' => [
                [
                    'bankID' => 3,
                    'bankName' => 'BCA',
                    'bankAccountName' => 'PT Sumber Pangan',
                    'bankAccountNumber' => '1234566789',
                ],
            ],
            'supplierBankOy' => [],
            'supplierProduct' => [
                [
                    'ID' => 900,
                    'productDetailID' => 501,
                    'productName' => 'Beras Premium',
                    'externalProductCode' => 'SP-BRS-01',
                    'uomName' => 'KG',
                ],
            ],
            'imageAttachments' => ['https://cdn.example.test/npwp.jpg'],
            'createdBy' => 'admin',
            'createdDate' => '2026-01-10 08:00:00',
            'editedBy' => '',
            'editedDate' => '',
            ...$overrides,
        ]);
    }
}
