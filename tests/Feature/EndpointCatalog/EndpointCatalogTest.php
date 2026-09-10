<?php

namespace Tests\Feature\EndpointCatalog;

use App\Authorization\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EndpointCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->artisan('esb:import-catalog');
    }

    private function viewer(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Viewer->value);

        return $user;
    }

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('endpoints.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function a_user_without_the_permission_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('endpoints.index'))
            ->assertForbidden();
    }

    #[Test]
    public function a_viewer_can_browse_the_catalog(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('endpoints.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('endpoints/index')
                ->has('endpoints.data')
                ->where('endpoints.total', 401),
            );
    }

    #[Test]
    public function it_searches_by_name_and_path(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('endpoints.index', ['search' => 'product/list']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('endpoints.data.0.path', '/product/list'),
            );
    }

    #[Test]
    public function it_filters_by_group(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('endpoints.index', ['group' => 'Master_Product']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('endpoints.total', 25),
            );
    }

    #[Test]
    public function it_filters_by_method(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('endpoints.index', ['method' => 'DELETE']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('endpoints.total', 35),
            );
    }

    #[Test]
    public function it_filters_by_family(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('endpoints.index', ['family' => 'voucher']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('endpoints.total', 3),
            );
    }

    #[Test]
    public function an_unknown_family_filter_is_rejected(): void
    {
        $this->actingAs($this->viewer())
            ->get(route('endpoints.index', ['family' => 'nonsense']))
            ->assertSessionHasErrors('family');
    }

    #[Test]
    public function it_offers_the_filter_options_it_actually_holds(): void
    {
        // Offering a group with nothing in it would be a dead end.
        //
        // 49, not 51: Core declares 41 groups and OMS 10, but "Authorization"
        // and "Report" appear in both products. The filter matches on group
        // name, so those are one option each.
        $this->actingAs($this->viewer())
            ->get(route('endpoints.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('groups', 49)
                ->has('methods')
                ->has('families'),
            );
    }

    #[Test]
    public function the_catalog_is_read_only(): void
    {
        // It mirrors the vendor documentation. Editing a row here would create
        // a second source of truth that silently disagrees with docs/esb/.
        $this->assertFalse(app('router')->has('endpoints.update'));
        $this->assertFalse(app('router')->has('endpoints.store'));
    }
}
