<?php

namespace App\Actions\SimplePurchases;

use App\Actions\Support\ApprovableDocument;
use App\Audit\AuditAction;

/**
 * How simple purchase writes are locked and audited (see DocumentWrites).
 */
final class SimplePurchaseDocument
{
    public static function approvable(): ApprovableDocument
    {
        return new ApprovableDocument(
            lockPrefix: 'simple-purchase',
            authorized: AuditAction::SimplePurchaseAuthorized,
            rejected: AuditAction::SimplePurchaseRejected,
            deleted: AuditAction::SimplePurchaseDeleted,
            writeUnconfirmed: AuditAction::SimplePurchaseWriteUnconfirmed,
        );
    }
}
