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
    }
}
