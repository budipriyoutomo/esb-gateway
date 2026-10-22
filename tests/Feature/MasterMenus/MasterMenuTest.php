<?php

namespace Tests\Feature\MasterMenus;

use App\Authorization\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\OmsGetMenu;
use Tests\TestCase;

/**
 * OMS master menu, read-only: GET {{fnb_backend}}/corev1/master/get-menu
 * ([ESB-013]). The vendor documents no detail operation, so the detail page
 * reads the list filtered by menu code and keeps only an exact match. The
 * list itself is the oms tab of /menus (ADR-030, MenuTest).
 */
class MasterMenuTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://stg7.esb.co.id/api-fnb-backend-int/web/corev1/master/get-menu';

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

    // ================================================================= access

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('master-menus.show', 'PS01'))->assertRedirect(route('login'));
    }

    #[Test]
    public function reading_erp_menus_needs_esb_invoke(): void
    {
        Http::fake();

        foreach ([Role::Viewer, Role::ItAdmin] as $role) {
            $this->actingAs($this->userWithRole($role))->get(route('master-menus.show', 'PS01'))->assertForbidden();
        }

        Http::assertNothingSent();
    }

    // =================================================================== list

    #[Test]
    public function the_old_list_url_moves_to_the_oms_tab_of_the_menu_list(): void
    {
        // ADR-030: one menu list; the OMS list is its oms tab.
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('master-menus.index', ['menuCode' => 'PS01', 'page' => 2, 'other' => 'x']))
            ->assertStatus(301)
            ->assertRedirect(route('menus.index', ['source' => 'oms', 'page' => 2, 'menuCode' => 'PS01']));

        Http::assertNothingSent();
    }

    // ================================================================= detail

    #[Test]
    public function the_detail_shows_the_menu_with_the_exact_code(): void
    {
        Http::fake([self::URL.'*' => Http::response(OmsGetMenu::response([
            OmsGetMenu::row(['menuCode' => 'PS01X', 'menuName' => 'Someone else']),
            OmsGetMenu::row(),
        ]))]);

        $this->actingAs($this->operator())
            ->get(route('master-menus.show', 'PS01'))
            ->assertOk()
            ->assertDontSee('https://cdn.esb.example')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('master-menus/show')
                ->where('menu.code', 'PS01')
                ->where('menu.name', 'Paket Separate')
                ->has('menu.templates', 2)
                ->has('menu.packageGroups.0.items', 2)
                ->where('menu.extras.0.name', 'Beef Burger'));

        Http::assertSent(fn (Request $request) => $request->url() === self::URL.'?page=1&menuCode=PS01');
    }

    #[Test]
    public function a_code_the_esb_does_not_return_exactly_is_not_found(): void
    {
        Http::fake([self::URL.'*' => Http::sequence()
            ->push(OmsGetMenu::response([OmsGetMenu::row(['menuCode' => 'PS010'])]))
            ->push(OmsGetMenu::response([], count: 0))]);

        $operator = $this->operator();

        $this->actingAs($operator)->get(route('master-menus.show', 'PS01'))->assertNotFound();
        $this->actingAs($operator)->get(route('master-menus.show', 'PS01'))->assertNotFound();
    }

    #[Test]
    public function a_code_that_is_not_a_plain_code_never_reaches_the_esb(): void
    {
        Http::fake();

        $this->actingAs($this->operator())->get('/master-menus/'.rawurlencode('a/b'))->assertNotFound();
        $this->actingAs($this->operator())->get('/master-menus/'.str_repeat('x', 51))->assertNotFound();

        Http::assertNothingSent();
    }
}
