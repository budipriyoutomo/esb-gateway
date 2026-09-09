<?php

namespace Tests\Feature\Authorization;

use App\Authorization\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Authorization must be enforced on the server. Hiding a link in React is a
 * usability affordance, never the security boundary
 * (docs/06-authentication-authorization.md).
 */
class RouteAuthorizationTest extends TestCase
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
    public function guests_are_redirected_to_login_from_the_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    #[Test]
    public function guests_are_redirected_to_login_from_user_administration(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function a_user_without_a_role_is_forbidden_from_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
    }

    #[Test]
    public function every_role_can_reach_the_dashboard(): void
    {
        foreach (Role::cases() as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get(route('dashboard'))
                ->assertOk();
        }
    }

    #[Test]
    public function a_viewer_is_forbidden_from_user_administration(): void
    {
        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('users.index'))
            ->assertForbidden();
    }

    #[Test]
    public function an_integration_operator_is_forbidden_from_user_administration(): void
    {
        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->get(route('users.index'))
            ->assertForbidden();
    }

    #[Test]
    public function an_it_admin_can_reach_user_administration(): void
    {
        $this->actingAs($this->userWithRole(Role::ItAdmin))
            ->get(route('users.index'))
            ->assertOk();
    }

    #[Test]
    public function a_super_admin_can_reach_user_administration(): void
    {
        $this->actingAs($this->userWithRole(Role::SuperAdmin))
            ->get(route('users.index'))
            ->assertOk();
    }

    #[Test]
    public function a_viewer_cannot_assign_roles_even_by_posting_directly(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->userWithRole(Role::Viewer))
            ->put(route('users.roles.update', $target), ['role' => Role::SuperAdmin->value])
            ->assertForbidden();

        $this->assertFalse($target->fresh()?->hasRole(Role::SuperAdmin->value));
    }

    #[Test]
    public function an_it_admin_can_assign_a_role(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->userWithRole(Role::ItAdmin))
            ->put(route('users.roles.update', $target), ['role' => Role::Viewer->value])
            ->assertRedirect();

        $this->assertTrue($target->fresh()?->hasRole(Role::Viewer->value));
    }

    #[Test]
    public function assigning_an_unknown_role_is_rejected(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->userWithRole(Role::ItAdmin))
            ->put(route('users.roles.update', $target), ['role' => 'Emperor'])
            ->assertSessionHasErrors('role');

        $this->assertSame([], $target->fresh()?->getRoleNames()->all());
    }

    #[Test]
    public function a_user_cannot_change_their_own_role(): void
    {
        $admin = $this->userWithRole(Role::ItAdmin);

        // Otherwise an IT Admin could quietly promote themselves to Super Admin,
        // which would defeat the separation the role set exists to create.
        $this->actingAs($admin)
            ->put(route('users.roles.update', $admin), ['role' => Role::SuperAdmin->value])
            ->assertForbidden();

        $this->assertFalse($admin->fresh()?->hasRole(Role::SuperAdmin->value));
    }

    #[Test]
    public function not_even_a_super_admin_can_change_their_own_role(): void
    {
        $superAdmin = $this->userWithRole(Role::SuperAdmin);

        // Super Admin wins every other ability through a Gate::before hook.
        // "assignRole" is deliberately excluded from that short-circuit so the
        // no-self-promotion rule holds for everyone.
        $this->actingAs($superAdmin)
            ->put(route('users.roles.update', $superAdmin), ['role' => Role::Viewer->value])
            ->assertForbidden();

        $this->assertTrue($superAdmin->fresh()?->hasRole(Role::SuperAdmin->value));
    }

    #[Test]
    public function a_super_admin_can_still_change_someone_elses_role(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->userWithRole(Role::SuperAdmin))
            ->put(route('users.roles.update', $target), ['role' => Role::ItAdmin->value])
            ->assertRedirect();

        $this->assertTrue($target->fresh()?->hasRole(Role::ItAdmin->value));
    }
}
