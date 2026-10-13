<?php

namespace Database\Seeders;

use App\Actions\Users\AssignUserRole;
use App\Authorization\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Seeds one signed-in-able account per role for local and CI work.
 *
 * A fresh installation cannot be logged into at all: Fortify's registration
 * feature was removed at scaffold time, DatabaseSeeder deliberately creates no
 * users, and `user:assign-role` only promotes an account that already exists.
 * That is correct for production but leaves developers with no way in.
 *
 * Deliberately NOT called from DatabaseSeeder: `php artisan db:seed` must keep
 * creating zero accounts, so that a deployment never gains users with known
 * passwords. Run it explicitly instead:
 *
 *     php artisan db:seed --class=DevUserSeeder
 *
 * Roles are assigned through AssignUserRole rather than by touching the pivot
 * directly, so the one-role-per-user rule and the audit trail hold here exactly
 * as they do in the UI and in `user:assign-role`.
 */
class DevUserSeeder extends Seeder
{
    /**
     * The password every seeded account shares.
     *
     * Known and identical by design -- these accounts exist to be logged into
     * during development. The production guard in run() is what keeps that from
     * being a vulnerability, so do not weaken it to make this seeder reusable.
     */
    public const PASSWORD = 'password';

    /**
     * Email address per role. Emails are the identity here: re-running the
     * seeder matches on them, so an existing account is repaired rather than
     * duplicated.
     *
     * @return array<string, Role>
     */
    public static function accounts(): array
    {
        return [
            'superadmin@maharasa.test' => Role::SuperAdmin,
            'itadmin@maharasa.test' => Role::ItAdmin,
            'operator@maharasa.test' => Role::IntegrationOperator,
            'viewer@maharasa.test' => Role::Viewer,
        ];
    }

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                'DevUserSeeder creates accounts with a publicly known password and must never run in production. '
                .'Create the first account deliberately, then promote it with `php artisan user:assign-role`.'
            );
        }

        // Roles have to exist before they can be assigned, and this seeder is
        // meant to be runnable on its own. RolePermissionSeeder is idempotent,
        // so calling it here is safe even after a full db:seed.
        $this->call(RolePermissionSeeder::class);

        $assignUserRole = app(AssignUserRole::class);

        foreach (self::accounts() as $email => $role) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $role->value,
                    'password' => Hash::make(self::PASSWORD),
                    'email_verified_at' => now(),
                ],
            );

            $assignUserRole->handle($user, $role);
        }
    }
}
