<?php

namespace Tests\Feature;

use App\Authorization\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_with_a_role_can_visit_the_dashboard()
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(Role::Viewer->value);
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    /**
     * Authentication alone grants nothing. A user has to be given a role
     * before they can see anything in the gateway.
     */
    public function test_authenticated_users_without_a_role_are_forbidden()
    {
        $this->seed(RolePermissionSeeder::class);

        $this->actingAs(User::factory()->create());

        $this->get(route('dashboard'))->assertForbidden();
    }
}
