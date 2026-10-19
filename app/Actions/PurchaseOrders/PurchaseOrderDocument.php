<?php

namespace App\Actions\PurchaseOrders;

use App\Actions\Support\ApprovableDocument;
use App\Audit\AuditAction;

/**
 * How purchase order writes are locked and audited (see DocumentWrites).
 */
final class PurchaseOrderDocument
{
    public static function approvable(): ApprovableDocument
    {
        return new ApprovableDocument(
            lockPrefix: 'purchase-order',
            authorized: AuditAction::PurchaseOrderAuthorized,
            rejected: AuditAction::PurchaseOrderRejected,
            deleted: AuditAction::PurchaseOrderDeleted,
            writeUnconfirmed: AuditAction::PurchaseOrderWriteUnconfirmed,
        );
    }
}
