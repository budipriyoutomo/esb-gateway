<?php

namespace Tests\Feature;

use App\Authorization\Permission;
use App\Authorization\Role;
use App\Models\IntegrationEndpoint;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Tests\TestCase;

/**
 * `php artisan db:seed` has to leave a usable installation behind.
 *
 * The individual seeders and the catalog importer each have their own tests,
 * but nothing exercised them *through* DatabaseSeeder -- which is how a
 * deployment actually invokes them.
 */
class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_seeds_roles_and_permissions(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(count(Role::cases()), RoleModel::count());
        $this->assertSame(count(Permission::cases()), PermissionModel::count());
    }

    #[Test]
    public function it_imports_the_endpoint_catalog(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(401, IntegrationEndpoint::query()->count());
    }

    #[Test]
    public function it_creates_no_users(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Roles grant real access to ERP data, so the first account is created
        // deliberately and promoted with `php artisan user:assign-role`, not
        // conjured into every environment by a seeder.
        $this->assertSame(0, User::query()->count());
    }

    #[Test]
    public function it_is_safe_to_run_twice(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(count(Role::cases()), RoleModel::count());
        $this->assertSame(401, IntegrationEndpoint::query()->count());
    }
}
