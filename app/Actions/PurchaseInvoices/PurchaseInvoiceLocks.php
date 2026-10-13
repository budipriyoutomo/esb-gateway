<?php

namespace App\Actions\PurchaseInvoices;

/**
 * Lock keys for purchase invoice writes (see GuardedEsbWrite).
 */
final class PurchaseInvoiceLocks
{
    /**
     * Shared by every write to one existing document -- authorize, reject,
     * delete -- so none of them can race another.
     */
    public static function document(string $number): string
    {
        return 'purchase-invoice-write:'.$number;
    }
}
