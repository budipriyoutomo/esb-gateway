<?php

namespace Tests\Feature\Authorization;

use App\Authorization\Permission;
use App\Authorization\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    #[Test]
    public function it_seeds_every_documented_role(): void
    {
        foreach (Role::cases() as $role) {
            $this->assertDatabaseHas('roles', ['name' => $role->value]);
        }

        $this->assertSame(count(Role::cases()), RoleModel::count());
    }

    #[Test]
    public function it_seeds_every_defined_permission(): void
    {
        foreach (Permission::cases() as $permission) {
            $this->assertDatabaseHas('permissions', ['name' => $permission->value]);
        }

        $this->assertSame(count(Permission::cases()), PermissionModel::count());
    }

    #[Test]
    public function seeding_twice_does_not_duplicate_roles_or_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->assertSame(count(Role::cases()), RoleModel::count());
        $this->assertSame(count(Permission::cases()), PermissionModel::count());
    }

    #[Test]
    public function super_admin_is_granted_every_ability_including_undefined_ones(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SuperAdmin->value);

        foreach (Permission::cases() as $permission) {
            $this->assertTrue(
                $user->can($permission->value),
                "Super Admin should be able to {$permission->value}",
            );
        }

        // Gate::before short-circuits, so an ability that does not exist yet is
        // still granted. This is what makes Super Admin future-proof against
        // permissions added later.
        $this->assertTrue($user->can('some.permission.that.does.not.exist'));
    }

    #[Test]
    public function viewer_can_read_but_cannot_manage_or_invoke(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Viewer->value);

        $this->assertTrue($user->can(Permission::ViewDashboard->value));
        $this->assertTrue($user->can(Permission::ViewIntegrationLogs->value));

        $this->assertFalse($user->can(Permission::ManageUsers->value));
        $this->assertFalse($user->can(Permission::ManageEsbConnections->value));
        $this->assertFalse($user->can(Permission::InvokeEsbOperations->value));
    }

    #[Test]
    public function integration_operator_can_invoke_esb_but_cannot_manage_users(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::IntegrationOperator->value);

        $this->assertTrue($user->can(Permission::InvokeEsbOperations->value));
        $this->assertTrue($user->can(Permission::TestEsbConnections->value));
        $this->assertTrue($user->can(Permission::ViewIntegrationLogs->value));

        $this->assertFalse($user->can(Permission::ManageUsers->value));
        $this->assertFalse($user->can(Permission::ManageRoles->value));
        $this->assertFalse($user->can(Permission::ManageEsbConnections->value));
    }

    #[Test]
    public function it_admin_manages_configuration_but_cannot_invoke_esb_operations(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::ItAdmin->value);

        $this->assertTrue($user->can(Permission::ManageUsers->value));
        $this->assertTrue($user->can(Permission::ManageRoles->value));
        $this->assertTrue($user->can(Permission::ManageEsbConnections->value));
        $this->assertTrue($user->can(Permission::ViewAuditLogs->value));

        // Administering the gateway is a separate responsibility from running
        // ERP transactions through it. Keeping these apart is what makes the
        // audit trail meaningful (docs/10-security.md, least privilege).
        $this->assertFalse($user->can(Permission::InvokeEsbOperations->value));
    }

    #[Test]
    public function a_user_without_a_role_can_do_nothing(): void
    {
        $user = User::factory()->create();

        foreach (Permission::cases() as $permission) {
            $this->assertFalse(
                $user->can($permission->value),
                "A user with no role should not be able to {$permission->value}",
            );
        }
    }
}
