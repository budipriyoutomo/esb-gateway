<?php

namespace App\Actions\PurchaseRequests;

use App\Actions\Support\ApprovableDocument;
use App\Audit\AuditAction;

/**
 * How purchase request decisions and deletes are locked and audited (see
 * DocumentWrites).
 */
final class PurchaseRequestDocument
{
    public static function approvable(): ApprovableDocument
    {
        return new ApprovableDocument(
            lockPrefix: 'purchase-request',
            authorized: AuditAction::PurchaseRequestAuthorized,
            rejected: AuditAction::PurchaseRequestRejected,
            deleted: AuditAction::PurchaseRequestDeleted,
            writeUnconfirmed: AuditAction::PurchaseRequestWriteUnconfirmed,
        );
    }
}
