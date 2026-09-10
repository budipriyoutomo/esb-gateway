<?php

namespace App\Audit;

/**
 * Auditable actions.
 *
 * Values are stable identifiers written to the database, so renaming a case
 * value rewrites history -- add a new case instead. The human-readable label
 * belongs in the UI, not here.
 */
enum AuditAction: string
{
    case RoleAssigned = 'user.role.assigned';

    /**
     * An ESB connection was repointed or its credential replaced (ADR-018).
     * The metadata names which fields changed; it never carries a value.
     */
    case EsbConnectionUpdated = 'esb-connection.updated';

    /** An ESB connection override was dropped, handing the family back to configuration. */
    case EsbConnectionReverted = 'esb-connection.reverted';
}
