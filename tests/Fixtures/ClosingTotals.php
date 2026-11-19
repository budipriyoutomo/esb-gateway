<?php

namespace Tests\Fixtures;

/**
 * A closing recap as sync-api Maharasa sends it, transcribed from the example
 * in docs/spesifikasi-penerima-kirim-closing.md §3 (contract revision 2,
 * ADR-032).
 */
final class ClosingTotals
{
    public const MESSAGE_ID = '6f1c2a7e-3b4d-4e8f-9a10-2b3c4d5e6f70';

    public const MENU_ID = '0b6f2c1e-8a2d-4c3b-9e7f-1a2b3c4d5e6f';

    /**
     * @param  array<string, mixed>  $overrides  Top-level fields to replace.
     * @return array<string, mixed>
     */
    public static function payload(array $overrides = []): array
    {
        return [
            'messageId' => self::MESSAGE_ID,
            'sentAt' => '2026-10-13T03:00:00Z',
            'sequence' => 2,
            'outlet' => ['code' => 'STTSM'],
            'productionDate' => '2026-10-12',
            'items' => [self::closingItem(), self::posOnlyItem()],
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function closingItem(array $overrides = []): array
    {
        return [
            'source' => 'closing',
            'menuId' => self::MENU_ID,
            'menuCode' => 'SU-001',
            'menuName' => 'Sushi Roll Salmon',
            'productId' => null,
            'productCode' => null,
            'productName' => null,
            'colorplateSold' => 10,
            'posQty' => 3,
            'totalSold' => 13,
            'waste' => 1,
            'adjustment' => -1,
            'compensation' => 0,
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function posOnlyItem(array $overrides = []): array
    {
        return [
            'source' => 'pos_only',
            'menuId' => null,
            'menuCode' => null,
            'menuName' => null,
            'productId' => '456',
            'productCode' => 'DRK-OCHA-01',
            'productName' => 'Ocha Dingin',
            'colorplateSold' => 0,
            'posQty' => 2,
            'totalSold' => 2,
            'waste' => 0,
            'adjustment' => 0,
            'compensation' => 0,
            ...$overrides,
        ];
    }

    /**
     * The same recap with the sales data of contract revision 3 as sync-api
     * proposed it (docs/usulan-kontrak-kirim-closing.md §2.2-§2.4): one sales
     * element per visit purpose, each with its own payments, and per-item
     * prices that name their visit purpose and add up to totalSold.
     *
     * @param  array<string, mixed>  $overrides  Top-level fields to replace.
     * @return array<string, mixed>
     */
    public static function revision3(array $overrides = []): array
    {
        return self::payload([
            'sales' => [self::sales()],
            'items' => [
                self::closingItem(['prices' => [self::price(13, 15000)]]),
                self::posOnlyItem(['prices' => [self::price(2, 8000)]]),
            ],
            ...$overrides,
        ]);
    }

    /**
     * Two visit purposes: the menu sold 10 dine in and 3 take away, the drink
     * 2 dine in.
     *
     * @param  array<string, mixed>  $overrides  Top-level fields to replace.
     * @return array<string, mixed>
     */
    public static function twoPurposes(array $overrides = []): array
    {
        return self::payload([
            'sales' => [
                self::sales([
                    'subtotal' => 166000, 'otherTaxTotal' => 8300, 'vatTotal' => 16600,
                    'grandTotal' => 190900, 'paymentTotal' => 190900,
                    'payments' => [self::payment(['paymentAmount' => 190900, 'fullPaymentAmount' => 190900])],
                ]),
                self::sales([
                    'visitPurposeName' => 'TAKE AWAY',
                    'subtotal' => 45000, 'otherTaxTotal' => 2250, 'vatTotal' => 4500,
                    'grandTotal' => 51750, 'paymentTotal' => 51750,
                    'payments' => [self::payment(['paymentMethod' => 'QRIS', 'paymentAmount' => 51750, 'fullPaymentAmount' => 51750])],
                ]),
            ],
            'items' => [
                self::closingItem(['prices' => [self::price(10, 15000), self::price(3, 15000, 'TAKE AWAY')]]),
                self::posOnlyItem(['prices' => [self::price(2, 8000)]]),
            ],
            ...$overrides,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function sales(array $overrides = []): array
    {
        return [
            'visitPurposeName' => 'DINE IN',
            'paxTotal' => 0,
            'flagInclusive' => 0,
            'subtotal' => 211000,
            'discountTotal' => 0,
            'menuDiscountTotal' => 0,
            'promotionDiscount' => 0,
            'otherTaxTotal' => 10550,
            'vatTotal' => 21100,
            'otherVatTotal' => 0,
            'deliveryFee' => 0,
            'orderFee' => 0,
            'voucherTotal' => 0,
            'roundingTotal' => 0,
            'grandTotal' => 242650,
            'paymentTotal' => 242650,
            'payments' => [self::payment()],
            ...$overrides,
        ];
    }

    /**
     * A payment as sync-api sends it: no coaNo or merchantDiscountRate, which
     * the gateway maps per paymentMethod.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function payment(array $overrides = []): array
    {
        return [
            'paymentMethod' => 'Cash',
            'paymentAmount' => 242650,
            'fullPaymentAmount' => 242650,
            'notes' => '',
            ...$overrides,
        ];
    }

    /**
     * One price line: 5% other tax and 10% VAT on qty x price.
     *
     * @return array<string, mixed>
     */
    public static function price(int|float $qty, int $price, string $visitPurposeName = 'DINE IN'): array
    {
        $base = $qty * $price;

        return [
            'visitPurposeName' => $visitPurposeName,
            'qty' => $qty,
            'originalPrice' => $price,
            'price' => $price,
            'discount' => 0,
            'discountValue' => 0,
            'otherTax' => 5,
            'otherTaxValue' => round($base * 5 / 100, 2),
            'vat' => 10,
            'vatValue' => round($base * 10 / 100, 2),
            'otherVat' => 0,
            'otherVatValue' => 0,
            'otherTaxOnVat' => 0,
            'total' => round($base * 115 / 100, 2),
        ];
    }

    /**
     * The body as the sender encodes it: UTF-8, non-ASCII not escaped.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function encode(array $payload): string
    {
        return (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
