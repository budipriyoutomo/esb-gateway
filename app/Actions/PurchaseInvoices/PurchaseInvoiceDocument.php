<?php

namespace App\Actions\PurchaseInvoices;

use App\Actions\Support\ApprovableDocument;
use App\Audit\AuditAction;

/**
 * How purchase invoice writes are locked and audited (see DocumentWrites).
 */
final class PurchaseInvoiceDocument
{
    public static function approvable(): ApprovableDocument
    {
        return new ApprovableDocument(
            lockPrefix: 'purchase-invoice',
            authorized: AuditAction::PurchaseInvoiceAuthorized,
            rejected: AuditAction::PurchaseInvoiceRejected,
            deleted: AuditAction::PurchaseInvoiceDeleted,
            writeUnconfirmed: AuditAction::PurchaseInvoiceWriteUnconfirmed,
        );
    }
}
