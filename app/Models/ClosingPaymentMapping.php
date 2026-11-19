<?php

namespace App\Models;

use App\Support\Decimal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A POS payment method of a closing recap -> the ERP account and merchant
 * discount rate Push Sales records it with (ADR-032, P30). sync-api sends
 * only the method's name; the account is the gateway's to decide.
 *
 * @property int $id
 * @property string $payment_method
 * @property string $coa_no
 * @property string|null $coa_name
 * @property string $merchant_discount_rate
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ClosingPaymentMapping extends Model
{
    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'merchant_discount_rate' => 'decimal:4',
        ];
    }

    /**
     * A payment method as the gateway matches it: trimmed and upper-cased.
     */
    public static function keyOf(string $paymentMethod): string
    {
        return mb_strtoupper(trim($paymentMethod));
    }

    /**
     * The mapping of a payment method, however the POS spells its case.
     */
    public static function findFor(string $paymentMethod): ?self
    {
        return self::query()
            ->whereRaw('UPPER(payment_method) = ?', [self::keyOf($paymentMethod)])
            ->first();
    }

    /**
     * @return array{paymentMethod: string, coaNo: string, coaName: string|null, merchantDiscountRate: string}
     */
    public function toView(): array
    {
        return [
            'paymentMethod' => $this->payment_method,
            'coaNo' => $this->coa_no,
            'coaName' => $this->coa_name,
            'merchantDiscountRate' => Decimal::trim($this->merchant_discount_rate),
        ];
    }
}
