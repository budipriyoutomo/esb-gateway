<?php

namespace App\Actions\Users;

use App\Audit\AuditAction;
use App\Authorization\Role;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Assign a role to a user, replacing any role they already hold.
 *
 * Roles are exclusive in this application: a user has exactly one, so the
 * effective permission set is always readable from a single value. Spatie
 * supports multiple roles per user, so this is a deliberate constraint rather
 * than a limitation of the package -- revisit it here if the business confirms
 * that someone needs to hold two roles at once.
 */
class AssignUserRole
{
    public function handle(User $user, Role $role): User
    {
        $previousRole = $user->getRoleNames()->first();

        // The role change and its audit entry succeed or fail together --
        // a privileged change that leaves no trace is the failure mode this
        // guards against (docs/04-functional-requirements.md).
        return DB::transaction(function () use ($user, $role, $previousRole): User {
            $user->syncRoles([$role->value]);

            AuditLog::record(
                action: AuditAction::RoleAssigned,
                auditable: $user,
                metadata: [
                    'from' => $previousRole,
                    'to' => $role->value,
                ],
            );

            return $user->refresh();
        });
    }
}
