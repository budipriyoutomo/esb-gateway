<?php

namespace App\Providers;

use App\Authorization\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthorizationServiceProvider extends ServiceProvider
{
    /**
     * Abilities Super Admin does not automatically win.
     *
     * The blanket grant below exists so Super Admin keeps working as new
     * permissions are added. But a few checks encode a rule that must hold for
     * everyone -- notably "nobody changes their own role", which is the guard
     * against silent self-promotion. Short-circuiting those would quietly
     * remove the protection the policy was written to provide.
     *
     * @var list<string>
     */
    protected const ABILITIES_DECIDED_BY_POLICY = [
        'assignRole',
    ];

    public function boot(): void
    {
        $this->grantSuperAdminEveryAbility();
    }

    /**
     * Short-circuit authorization checks for Super Admin.
     *
     * Returning null (rather than false) defers to the normal gate, policy, and
     * permission checks, both for non-Super-Admins and for the abilities listed
     * above.
     */
    protected function grantSuperAdminEveryAbility(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            if (in_array($ability, self::ABILITIES_DECIDED_BY_POLICY, true)) {
                return null;
            }

            return $user->hasRole(Role::SuperAdmin->value) ? true : null;
        });
    }
}
