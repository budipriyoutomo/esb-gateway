<?php

namespace Tests\Feature\Authorization;

use App\Authorization\Permission;
use App\Authorization\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The frontend needs to know what the current user may do so it can hide
 * controls they cannot use. This is a usability affordance only -- the server
 * still enforces every check (see RouteAuthorizationTest).
 */
class SharedPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    #[Test]
    public function a_viewers_shared_permissions_match_their_role(): void
    {
        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.role', Role::Viewer->value)
                ->where('auth.permissions', Role::Viewer->permissionNames()),
            );
    }

    #[Test]
    public function a_super_admin_shares_every_permission(): void
    {
        $this->actingAs($this->userWithRole(Role::SuperAdmin))
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.role', Role::SuperAdmin->value)
                ->where('auth.permissions', Permission::names()),
            );
    }

    #[Test]
    public function shared_permissions_never_leak_a_password_or_two_factor_secret(): void
    {
        $user = $this->userWithRole(Role::ItAdmin);
        $user->forceFill(['two_factor_secret' => encrypt('shhh')])->save();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->missing('auth.user.password')
            ->missing('auth.user.two_factor_secret')
            ->missing('auth.user.two_factor_recovery_codes')
            ->missing('auth.user.remember_token'),
        );
    }

    #[Test]
    public function a_guest_shares_no_permissions(): void
    {
        $this->get(route('home'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.user', null)
                ->where('auth.role', null)
                ->where('auth.permissions', []),
            );
    }
}
