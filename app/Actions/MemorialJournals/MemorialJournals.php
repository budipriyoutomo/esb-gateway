<?php

namespace App\Actions\MemorialJournals;

use App\Actions\Support\ApprovableDocument;
use App\Audit\AuditAction;

/**
 * How a memorial journal's writes are locked and audited (see
 * DocumentWrites). One lock per journal covers every write to it: decisions,
 * delete, update, images.
 */
final class MemorialJournals
{
    public static function approvable(): ApprovableDocument
    {
        return new ApprovableDocument(
            lockPrefix: 'memorial-journal',
            authorized: AuditAction::MemorialJournalAuthorized,
            rejected: AuditAction::MemorialJournalRejected,
            deleted: AuditAction::MemorialJournalDeleted,
            writeUnconfirmed: AuditAction::MemorialJournalWriteUnconfirmed,
        );
    }
}
