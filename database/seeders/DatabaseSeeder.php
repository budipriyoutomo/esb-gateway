<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

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
        //
        // Artisan::call, not $this->call: Seeder::call expects a seeder class,
        // and passing a command name to it fails at runtime rather than at
        // compile time.
        Artisan::call('esb:import-catalog');
    }
}
