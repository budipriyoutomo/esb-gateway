<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * No users are created here. Roles grant real access to ERP data, so the
     * first account is created deliberately and promoted with
     * `php artisan user:assign-role <email> "Super Admin"` rather than
     * appearing by default in every environment.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        // The endpoint catalog is imported from the generated files in
        // docs/esb/ rather than seeded inline, so there is one source of truth
        // for what the ESB offers.
        $this->callSilent('esb:import-catalog');
    }
}
