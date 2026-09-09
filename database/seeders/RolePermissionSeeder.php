<?php

namespace Database\Seeders;

use App\Authorization\Permission;
use App\Authorization\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the roles and permissions defined in App\Authorization.
 *
 * Idempotent: safe to re-run after adding a permission or changing a role's
 * grants. Permissions removed from the enum are also removed from the database,
 * so the enum stays the single source of truth.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = config('auth.defaults.guard');

        foreach (Permission::names() as $name) {
            PermissionModel::findOrCreate($name, $guard);
        }

        // Drop permissions that no longer exist in the enum, so a removed
        // ability cannot linger and keep granting access.
        PermissionModel::query()
            ->whereNotIn('name', Permission::names())
            ->delete();

        foreach (Role::cases() as $role) {
            $model = RoleModel::findOrCreate($role->value, $guard);
            $model->syncPermissions($role->permissionNames());
        }

        RoleModel::query()
            ->whereNotIn('name', array_column(Role::cases(), 'value'))
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
