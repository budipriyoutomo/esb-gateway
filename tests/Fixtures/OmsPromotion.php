<?php

namespace Tests\Fixtures;

/**
 * ESB OMS Master Promotion responses ([ESB-049], [ESB-050]), transcribed
 * from the vendor's examples. The list is a bare JSON array (ADR-027).
 */
final class OmsPromotion
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function row(array $overrides = []): array
    {
        return [
            'promotionID' => 52,
            'promotionCode' => 'PROMOESO50K',
            'promotionTypeDesc' => 'PROMO DISCOUNT (Rp) ESO',
            'notes' => 'PROMOESO50K',
            'discount' => 50000,
            'minSubtotal' => 0,
            'startDate' => '2023-06-01 00:00:00',
            'endDate' => '2025-12-31 00:00:00',
            'flagShow' => true,
            'promotionCategory' => [],
            'branches' => [
                ['branchID' => 2, 'branchCode' => 'OUT1', 'branchName' => 'OUTLET Tangerang 1'],
            ],
            'selfOrderPaymentMethods' => [
                ['selfOrderPaymentMethodID' => 'ovo', 'selfOrderPaymentMethodName' => 'OVO (Xendit)'],
                ['selfOrderPaymentMethodID' => 'qrisesb', 'selfOrderPaymentMethodName' => 'QRIS (ESB)'],
            ],
            'paymentMethod' => ['paymentMethodID' => 1, 'paymentMethodName' => 'DANA'],
            ...$overrides,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function saved(int $id = 119, string $notes = 'Promo'): array
    {
        return [
            'path' => 'http://stg7.esb.co.id/api-fnb-backend-int/web/corev1/promotion/',
            'code' => 200,
            'timestamp' => '2023-11-10 09:38:57',
            'message' => 'Save Data Successfully',
            'data' => ['promotionID' => $id, 'notes' => $notes],
        ];
    }

    /**
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    public static function refused(array $errors): array
    {
        return [
            'path' => 'http://stg7.esb.co.id/api-fnb-backend-int/web/corev1/promotion/',
            'code' => 400,
            'timestamp' => '2023-08-07 14:06:41',
            'message' => '{Attribute} cannot be blank.',
            'data' => $errors,
        ];
    }
}
