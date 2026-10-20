<?php

namespace App\Actions\Pricelists;

use App\Actions\Support\ApprovableDocument;
use App\Audit\AuditAction;

/**
 * How temporary pricelist writes are locked and audited (see DocumentWrites).
 */
final class TempPricelistDocument
{
    public static function approvable(): ApprovableDocument
    {
        return new ApprovableDocument(
            lockPrefix: 'pricelist',
            authorized: AuditAction::PricelistAuthorized,
            rejected: AuditAction::PricelistRejected,
            deleted: AuditAction::PricelistDeleted,
            writeUnconfirmed: AuditAction::PricelistWriteUnconfirmed,
        );
    }
}
