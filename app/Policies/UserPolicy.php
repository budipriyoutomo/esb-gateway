<?php

namespace App\Policies;

use App\Authorization\Permission;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewUsers->value);
    }

    public function view(User $user): bool
    {
        return $user->can(Permission::ViewUsers->value);
    }

    public function update(User $user): bool
    {
        return $user->can(Permission::ManageUsers->value);
    }

    /**
     * Decide whether $user may change $target's role.
     *
     * Nobody changes their own role, Super Admin included. Otherwise an IT
     * Admin could quietly promote themselves and erase the separation between
     * administering the gateway and running ERP transactions through it
     * (docs/10-security.md, privilege escalation).
     */
    public function assignRole(User $user, User $target): bool
    {
        if ($user->is($target)) {
            return false;
        }

        return $user->can(Permission::ManageUsers->value);
    }
}
