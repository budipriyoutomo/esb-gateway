<?php

namespace App\Console\Commands;

use App\Actions\Users\AssignUserRole;
use App\Authorization\Role;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Assign a role from the console.
 *
 * This is the bootstrap path for the first Super Admin: granting a role through
 * the UI requires already holding users.manage, so a fresh installation would
 * otherwise have no way to promote anyone. Everyday role changes should go
 * through the UI, where the actor is recorded in the audit trail.
 */
class AssignRoleCommand extends Command
{
    protected $signature = 'user:assign-role
        {email : Email address of the user}
        {role : Role name, e.g. "Super Admin"}';

    protected $description = 'Assign a role to a user by email address';

    public function handle(AssignUserRole $assignUserRole): int
    {
        $email = (string) $this->argument('email');
        $roleName = (string) $this->argument('role');

        $role = Role::tryFrom($roleName);

        if ($role === null) {
            $this->error(sprintf(
                'Unknown role "%s". Available roles: %s',
                $roleName,
                implode(', ', array_column(Role::cases(), 'value')),
            ));

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error(sprintf('No user found with email "%s".', $email));

            return self::FAILURE;
        }

        $assignUserRole->handle($user, $role);

        $this->info(sprintf('%s is now %s.', $user->email, $role->value));

        return self::SUCCESS;
    }
}
