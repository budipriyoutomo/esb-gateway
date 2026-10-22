<?php

namespace App\Actions\PurchaseRequests;

/**
 * Lock keys for purchase request writes (see GuardedEsbWrite).
 */
final class PurchaseRequestLocks
{
    /**
     * Shared by every write to one existing document -- update, delete,
     * authorize, reject -- so none of them can race another.
     */
    public static function document(string $number): string
    {
        return PurchaseRequestDocument::approvable()->lock($number);
    }

    /**
     * A new document has no number to lock on until the ERP assigns one, so
     * a create is serialized per person: it stops a double-click, not two
     * people raising requests at once.
     */
    public static function create(int|string|null $userId): string
    {
        return 'purchase-request-create:'.($userId ?? 'guest');
    }

    public static function import(int|string|null $userId): string
    {
        return 'purchase-request-import:'.($userId ?? 'guest');
    }
}
