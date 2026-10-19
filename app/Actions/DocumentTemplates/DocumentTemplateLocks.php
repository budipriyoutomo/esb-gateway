<?php

namespace App\Actions\DocumentTemplates;

/**
 * Lock keys for document template writes (see GuardedEsbWrite).
 */
final class DocumentTemplateLocks
{
    /**
     * A new template has no id to lock on until the ERP assigns one, so a
     * create is serialized per person: it stops a double-click, not two
     * people creating templates at once.
     */
    public static function create(int|string|null $userId): string
    {
        return 'document-template-create:'.($userId ?? 'guest');
    }
}
