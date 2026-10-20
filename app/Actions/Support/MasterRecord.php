<?php

namespace App\Actions\Support;

use App\Audit\AuditAction;

/**
 * What RecordWrites needs to know about one kind of master data record: its
 * lock scope, which operations it supports, and what each one audits.
 */
final readonly class MasterRecord
{
    /**
     * @param  array<string, AuditAction>  $audits  RecordOperation value => action
     */
    public function __construct(
        public string $lockPrefix,
        public array $audits,
        public AuditAction $writeUnconfirmed,
    ) {}

    /**
     * One write per record at a time, whatever the operation.
     */
    public function lock(int|string $id): string
    {
        return $this->lockPrefix.'-write:'.$id;
    }

    /**
     * A new record has no id until the ERP makes one, so creates are one at
     * a time per person instead -- enough to stop a double submit.
     */
    public function createLock(int|string|null $userId): string
    {
        return $this->lockPrefix.'-create:'.($userId ?? 'guest');
    }

    public function supports(RecordOperation $operation): bool
    {
        return isset($this->audits[$operation->value]);
    }
}
