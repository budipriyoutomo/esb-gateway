<?php

namespace Tests\Feature\Audit;

use App\Audit\AuditAction;
use App\Authorization\Role;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class AuditLogTest extends TestCase
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
    public function assigning_a_role_records_an_audit_entry(): void
    {
        $admin = $this->userWithRole(Role::ItAdmin);
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->put(route('users.roles.update', $target), ['role' => Role::Viewer->value])
            ->assertRedirect();

        $entry = AuditLog::query()->sole();

        $this->assertSame(AuditAction::RoleAssigned->value, $entry->action);
        $this->assertSame($admin->id, $entry->actor_id, 'The actor is who made the change, not who it was made to.');
        $this->assertSame(User::class, $entry->auditable_type);
        $this->assertSame($target->id, $entry->auditable_id);
    }

    #[Test]
    public function the_audit_entry_records_the_role_before_and_after(): void
    {
        $admin = $this->userWithRole(Role::ItAdmin);
        $target = $this->userWithRole(Role::Viewer);

        $this->actingAs($admin)
            ->put(route('users.roles.update', $target), ['role' => Role::IntegrationOperator->value]);

        $entry = AuditLog::query()->sole();

        $this->assertSame(Role::Viewer->value, $entry->metadata['from'] ?? null);
        $this->assertSame(Role::IntegrationOperator->value, $entry->metadata['to'] ?? null);
    }

    #[Test]
    public function a_first_role_assignment_records_a_null_previous_role(): void
    {
        $admin = $this->userWithRole(Role::ItAdmin);
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->put(route('users.roles.update', $target), ['role' => Role::Viewer->value]);

        $entry = AuditLog::query()->sole();

        // The key must be present and null -- "no previous role" is a fact
        // worth recording, not an omission.
        $this->assertArrayHasKey('from', $entry->metadata);
        $this->assertNull($entry->metadata['from']);
        $this->assertSame(Role::Viewer->value, $entry->metadata['to'] ?? null);
    }

    #[Test]
    public function a_rejected_role_change_records_nothing(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->userWithRole(Role::Viewer))
            ->put(route('users.roles.update', $target), ['role' => Role::SuperAdmin->value])
            ->assertForbidden();

        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function audit_entries_cannot_be_updated(): void
    {
        $entry = AuditLog::record(AuditAction::RoleAssigned, null, null, []);

        $this->expectException(RuntimeException::class);

        $entry->update(['action' => 'something.else']);
    }

    #[Test]
    public function audit_entries_cannot_be_deleted(): void
    {
        $entry = AuditLog::record(AuditAction::RoleAssigned, null, null, []);

        $this->expectException(RuntimeException::class);

        $entry->delete();
    }

    #[Test]
    public function audit_metadata_never_stores_credentials(): void
    {
        $admin = $this->userWithRole(Role::ItAdmin);
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->put(route('users.roles.update', $target), ['role' => Role::Viewer->value]);

        $raw = json_encode(AuditLog::query()->sole()->toArray());

        $this->assertIsString($raw);
        $this->assertStringNotContainsString('password', $raw);
        $this->assertStringNotContainsString('two_factor_secret', $raw);
        $this->assertStringNotContainsString('remember_token', $raw);
    }
}
