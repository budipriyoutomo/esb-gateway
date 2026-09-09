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
}
