<?php

namespace App\Actions\Support;

/**
 * A write on a master data record (TODO-modules.md §2): S2's state changes
 * on an existing record, and S3's create / update.
 */
enum RecordOperation: string
{
    case Delete = 'delete';
    case Deactivate = 'deactivate';
    case Restore = 'restore';
    case Create = 'create';
    case Update = 'update';
    case Move = 'move';
    case Attach = 'attach';
    case Detach = 'detach';

    /** e.g. "deactivated", for messages. */
    public function pastTense(): string
    {
        return match ($this) {
            self::Delete => 'deleted',
            self::Deactivate => 'deactivated',
            self::Restore => 'restored',
            self::Create => 'created',
            self::Update => 'updated',
            self::Move => 'moved',
            self::Attach => 'given attachments',
            self::Detach => 'cleared of attachments',
        };
    }
}
