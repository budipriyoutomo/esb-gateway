<?php

namespace App\Actions\Support;

use App\Audit\AuditAction;

/**
 * What DocumentWrites needs to know about one kind of approvable document:
 * its lock scope and the audit actions its writes record.
 */
final readonly class ApprovableDocument
{
    public function __construct(
        public string $lockPrefix,
        public AuditAction $authorized,
        public AuditAction $rejected,
        public AuditAction $deleted,
        public AuditAction $writeUnconfirmed,
    ) {}

    /**
     * Shared by every write to one existing document -- authorize, reject,
     * delete -- so none of them can race another.
     */
    public function lock(string $number): string
    {
        return $this->lockPrefix.'-write:'.$number;
    }
}
