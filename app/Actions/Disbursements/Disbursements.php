<?php

namespace App\Actions\Disbursements;

use App\Actions\Support\ApprovableDocument;
use App\Audit\AuditAction;

/**
 * How a disbursement's writes are locked and audited (see DocumentWrites).
 * One lock per disbursement covers every write to it: decisions, delete,
 * update, attachments.
 */
final class Disbursements
{
    public static function approvable(): ApprovableDocument
    {
        return new ApprovableDocument(
            lockPrefix: 'disbursement',
            authorized: AuditAction::DisbursementAuthorized,
            rejected: AuditAction::DisbursementRejected,
            deleted: AuditAction::DisbursementDeleted,
            writeUnconfirmed: AuditAction::DisbursementWriteUnconfirmed,
        );
    }
}
