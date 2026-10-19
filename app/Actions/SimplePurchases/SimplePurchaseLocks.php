<?php

namespace App\Actions\SimplePurchases;

/**
 * Lock keys for simple purchase writes (see GuardedEsbWrite).
 */
final class SimplePurchaseLocks
{
    /**
     * Shared by every write to one existing document -- authorize, reject,
     * delete -- so none of them can race another.
     */
    public static function document(string $number): string
    {
        return 'simple-purchase-write:'.$number;
    }
}
