<?php

namespace App\Actions\PurchaseOrders;

/**
 * Lock keys for purchase order writes (see GuardedEsbWrite).
 */
final class PurchaseOrderLocks
{
    /**
     * Shared by every write to one existing document -- authorize, reject,
     * delete -- so none of them can race another.
     */
    public static function document(string $number): string
    {
        return 'purchase-order-write:'.$number;
    }
}
