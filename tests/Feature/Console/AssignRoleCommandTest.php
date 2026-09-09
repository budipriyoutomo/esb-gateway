<?php

namespace Tests\Feature\Console;

use App\Audit\AuditAction;
use App\Authorization\Role;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * There has to be a way to create the first Super Admin, because granting a
 * role through the UI requires already holding users.manage. This command is
 * that bootstrap path, and it is why it runs without an authenticated actor.
 */
class AssignRoleCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    #[Test]
    public function it_assigns_a_role_by_email(): void
    {
        $user = User::factory()->create(['email' => 'ops@example.test']);

        $this->artisan('user:assign-role', [
            'email' => 'ops@example.test',
            'role' => Role::SuperAdmin->value,
        ])->assertSuccessful();

        $this->assertTrue($user->fresh()?->hasRole(Role::SuperAdmin->value));
    }

    #[Test]
    public function it_records_an_audit_entry_with_no_actor(): void
    {
        User::factory()->create(['email' => 'ops@example.test']);

        $this->artisan('user:assign-role', [
            'email' => 'ops@example.test',
            'role' => Role::ItAdmin->value,
        ])->assertSuccessful();

        $entry = AuditLog::query()->sole();

        $this->assertSame(AuditAction::RoleAssigned->value, $entry->action);
        $this->assertNull($entry->actor_id, 'A console-initiated change has no authenticated actor.');
    }

    #[Test]
    public function it_fails_for_an_unknown_email(): void
    {
        $this->artisan('user:assign-role', [
            'email' => 'nobody@example.test',
            'role' => Role::Viewer->value,
        ])->assertFailed();

        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function it_fails_for_an_unknown_role(): void
    {
        User::factory()->create(['email' => 'ops@example.test']);

        $this->artisan('user:assign-role', [
            'email' => 'ops@example.test',
            'role' => 'Emperor',
        ])->assertFailed();

        $this->assertSame(0, AuditLog::query()->count());
    }
}
