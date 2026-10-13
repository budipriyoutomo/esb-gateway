<?php

namespace Tests\Feature\Menus;

use App\Authorization\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\MenuBrowse;
use Tests\TestCase;

/**
 * Master menu list, read-only: GET {{core}}/menu/browse ([ESB-012]).
 *
 * The Core Menu group documents no detail operation, and the browse row
 * carries nothing a detail page could add, so there is only a list.
 */
class MenuTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://stg7.esb.co.id/core-int/menu/browse';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seed(RolePermissionSeeder::class);

        config()->set('esb.environment', 'staging_int');
        config()->set('esb.auth.mechanism', 'static_token');
        config()->set('esb.auth.static_token', 'a-static-api-key');
        config()->set('esb.retry.base_delay_ms', 0);
    }

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    private function operator(): User
    {
        return $this->userWithRole(Role::IntegrationOperator);
    }

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('menus.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function reading_erp_menus_needs_esb_invoke(): void
    {
        Http::fake();

        foreach ([Role::Viewer, Role::ItAdmin] as $role) {
            $this->actingAs($this->userWithRole($role))->get(route('menus.index'))->assertForbidden();
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function an_operator_can_list_menus_from_esb_core(): void
    {
        Http::fake([self::URL.'*' => Http::response(MenuBrowse::response(count: 45))]);

        $this->actingAs($this->operator())
            ->get(route('menus.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('menus/index')
                ->has('menus', 1)
                ->where('menus.0.code', 'PS01')
                ->where('menus.0.name', 'Paket Separate')
                ->where('menus.0.shortName', 'Paket Sep')
                ->where('menus.0.categoryName', 'Food')
                ->where('menus.0.categoryDetailName', 'Snack')
                ->where('pagination.page', 1)
                ->where('pagination.count', 45)
                ->where('pagination.lastPage', 3)
                ->where('filters', ['page' => 1, 'limit' => 20]));

        Http::assertSent(fn (Request $request) => $request->method() === 'GET'
            && $request->url() === self::URL.'?page=1&limit=20');
    }

    #[Test]
    public function the_list_can_be_filtered(): void
    {
        Http::fake([self::URL.'*' => Http::response(MenuBrowse::response(page: 2))]);

        $this->actingAs($this->operator())
            ->get(route('menus.index', [
                'page' => 2,
                'menuName' => 'Paket',
                'menuCode' => 'PS01',
                'flagActive' => 0,
            ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters', ['page' => 2, 'limit' => 20, 'menuName' => 'Paket', 'menuCode' => 'PS01', 'flagActive' => 0]));

        Http::assertSent(fn (Request $request) => $request->url()
            === self::URL.'?page=2&limit=20&menuName=Paket&menuCode=PS01&flagActive=0');
    }

    #[Test]
    public function bad_filters_are_refused_before_reaching_the_esb(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('menus.index', [
                'page' => 0,
                'menuCode' => str_repeat('x', 51),
                'menuName' => str_repeat('x', 101),
                'flagActive' => 2,
            ]))
            ->assertSessionHasErrors(['page', 'menuCode', 'menuName', 'flagActive']);

        Http::assertNothingSent();
    }

    #[Test]
    public function there_is_no_detail_page(): void
    {
        $this->actingAs($this->operator())->get('/menus/PS01')->assertNotFound();
    }
}
