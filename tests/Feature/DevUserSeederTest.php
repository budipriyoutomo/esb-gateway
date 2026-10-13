<?php

namespace Tests\Feature;

use App\Authorization\Role;
use App\Models\User;
use Database\Seeders\DevUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * A fresh installation has no users: registration is disabled and
 * DatabaseSeeder deliberately creates none, so there is nothing to log in
 * with. `user:assign-role` promotes an existing account but cannot create the
 * first one.
 *
 * This seeder fills that gap for local and CI work only. It stays out of
 * DatabaseSeeder so that a production `db:seed` still creates no accounts
 * (DatabaseSeederTest), and it refuses to run in production outright.
 */
class DevUserSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_one_user_per_role(): void
    {
        $this->seed(DevUserSeeder::class);

        $this->assertSame(count(Role::cases()), User::query()->count());

        foreach (Role::cases() as $role) {
            $this->assertSame(
                1,
                User::query()->whereHas('roles', fn ($query) => $query->where('name', $role->value))->count(),
                sprintf('Expected exactly one seeded user holding the %s role.', $role->value),
            );
        }
    }

    #[Test]
    public function seeded_users_can_log_in_with_the_documented_password(): void
    {
        $this->seed(DevUserSeeder::class);

        $response = $this->post(route('login.store'), [
            'email' => 'superadmin@maharasa.test',
            'password' => DevUserSeeder::PASSWORD,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    #[Test]
    public function the_seeded_super_admin_can_reach_the_dashboard(): void
    {
        $this->seed(DevUserSeeder::class);

        $user = User::query()->where('email', 'superadmin@maharasa.test')->sole();

        // Authenticating is only half of it: /dashboard is permission-gated, so
        // a seeded account with no role would look like a broken login.
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    #[Test]
    public function it_is_safe_to_run_twice(): void
    {
        $this->seed(DevUserSeeder::class);
        $this->seed(DevUserSeeder::class);

        $this->assertSame(count(Role::cases()), User::query()->count());
        $this->assertTrue(
            User::query()->where('email', 'superadmin@maharasa.test')->sole()
                ->hasRole(Role::SuperAdmin->value),
        );
    }

    #[Test]
    public function it_refuses_to_run_in_production(): void
    {
        app()->detectEnvironment(fn (): string => 'production');

        $this->expectException(RuntimeException::class);

        try {
            // Invoked directly rather than through $this->seed(): SeedCommand
            // stops for an interactive confirmation in production, which would
            // mask whether the seeder itself refuses.
            app(DevUserSeeder::class)->run();
        } finally {
            $this->assertSame(0, User::query()->count());
        }
    }
}
