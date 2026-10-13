<?php

namespace Tests\Fixtures;

/**
 * A Push Sales Data V2 body, transcribed from the vendor's "Body (JSON)"
 * example (docs/05-api-integration.md, [ESB-011]).
 *
 * The empty strings matter: the vendor marks those attributes "mandatory, but
 * can contain empty string", so they must reach the ESB as "" and not as null.
 */
final class SalesDataV2
{
    /**
     * @param  array<string, mixed>  $headOverrides
     * @return array{salesHead: array<string, mixed>}
     */
    public static function payload(array $headOverrides = []): array
    {
        return [
            'salesHead' => [
                'salesNum' => 'SRXT172777428297',
                'billNum' => 'RXT202410010010',
                'salesDate' => '2024-10-01',
                'salesDateIn' => '2024-10-01 16:20:00',
                'salesDateOut' => '2024-10-01 16:30:00',
                'branchCode' => 'RXT',
                'memberCode' => '',
                'customerName' => '',
                'visitPurposeName' => 'DINE IN (Not Inclusive)',
                'paxTotal' => 1,
                'subtotal' => 60000,
                'discountTotal' => 0,
                'menuDiscountTotal' => 0,
                'promotionDiscount' => 0,
                'otherTaxTotal' => 3000,
                'vatTotal' => 6000,
                'otherVatTotal' => 0,
                'deliveryFee' => 0,
                'orderFee' => 0,
                'grandTotal' => 69000,
                'voucherTotal' => 0,
                'roundingTotal' => 0,
                'paymentTotal' => 69000,
                'billingPrintCount' => 1,
                'paymentPrintCount' => 1,
                'additionalInfo' => '',
                'promotionID' => '0',
                'flagInclusive' => 0,
                'statusID' => 8,
                'createdBy' => 'A1FLX',
                'editedBy' => 'A1FLX',
                'editedDate' => '2024-10-01 16:30:00',
                'menu' => [
                    [
                        'menuID' => 188,
                        'menuCode' => 'JCE00001',
                        'qty' => 6,
                        'originalPrice' => 10000,
                        'price' => 10000,
                        'discount' => 0,
                        'discountValue' => 0,
                        'otherTax' => 5,
                        'otherTaxValue' => 3000,
                        'vat' => 10,
                        'vatValue' => 6000,
                        'otherVat' => 0,
                        'otherVatValue' => 0,
                        'otherTaxOnVat' => 0,
                        'total' => 69000,
                        'notes' => '',
                        'statusID' => 13,
                        'promotionDetailID' => 0,
                        'promotionID' => '0',
                        'cancelNotes' => '',
                        'createdBy' => 'A1FLX',
                        'createdDate' => '2024-10-01 16:00:00',
                        'editedDate' => '2024-10-01 16:30:00',
                        'packages' => [],
                        'extras' => [],
                    ],
                ],
                'payment' => [
                    [
                        'paymentMethod' => 'Cash',
                        'coaNo' => '1 1 01 01',
                        'voucherCode' => '',
                        'notes' => '',
                        'cardNumber' => '',
                        'bankName' => '',
                        'accountName' => '',
                        'verificationCode' => '',
                        'merchantDiscountRate' => 0,
                        'paymentAmount' => 69000,
                        'fullPaymentAmount' => 69000,
                    ],
                ],
                ...$headOverrides,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function successResponse(): array
    {
        return [
            'path' => 'http://stg7.esb.co.id/api-fnb-backend-int/web/extv1/push/sales-data',
            'timestamp' => '2024-10-01 16:42:46',
            'code' => '200',
            'status' => 'ok',
            'message' => null,
            'result' => ['status' => '00', 'syncDate' => '2024-10-01 16:42:46'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function notFoundResponse(): array
    {
        return [
            'path' => 'http://stg7.esb.co.id/api-fnb-backend-int/web/extv1/push/sales-data',
            'code' => 400,
            'timestamp' => '2024-10-04 13:44:52',
            'message' => 'Member not found: salesHead.memberCode',
            'data' => [
                'Member not found: salesHead.memberCode',
                'Visit Purpose not found: salesHead.visitPurposeName',
            ],
        ];
    }
}
