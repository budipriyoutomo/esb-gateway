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
 * reads the list filtered by menu code and keeps only an exact match.
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
        $this->get(route('master-menus.index'))->assertRedirect(route('login'));
        $this->get(route('master-menus.show', 'PS01'))->assertRedirect(route('login'));
    }

    #[Test]
    public function reading_erp_menus_needs_esb_invoke(): void
    {
        Http::fake();

        foreach ([Role::Viewer, Role::ItAdmin] as $role) {
            $user = $this->userWithRole($role);

            $this->actingAs($user)->get(route('master-menus.index'))->assertForbidden();
            $this->actingAs($user)->get(route('master-menus.show', 'PS01'))->assertForbidden();
        }

        Http::assertNothingSent();
    }

    // =================================================================== list

    #[Test]
    public function an_operator_can_list_menus(): void
    {
        Http::fake([self::URL.'*' => Http::response(OmsGetMenu::response(count: 45))]);

        $this->actingAs($this->operator())
            ->get(route('master-menus.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('master-menus/index')
                ->has('menus', 1)
                ->where('menus.0.code', 'PS01')
                ->where('menus.0.name', 'Paket Separate')
                ->where('menus.0.categoryName', 'Food - RX - Snack')
                ->where('pagination.page', 1)
                ->where('pagination.count', 45)
                ->where('pagination.lastPage', 3)
                ->where('filters', ['page' => 1]));

        Http::assertSent(fn (Request $request) => $request->method() === 'GET'
            && $request->url() === self::URL.'?page=1');
    }

    #[Test]
    public function the_list_can_be_filtered_by_code_and_paged(): void
    {
        Http::fake([self::URL.'*' => Http::response(OmsGetMenu::response())]);

        $this->actingAs($this->operator())
            ->get(route('master-menus.index', ['menuCode' => 'PS01', 'page' => 2]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters', ['page' => 2, 'menuCode' => 'PS01']));

        Http::assertSent(fn (Request $request) => $request->url() === self::URL.'?page=2&menuCode=PS01');
    }

    #[Test]
    public function bad_filters_are_refused_before_reaching_the_esb(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('master-menus.index', ['page' => 0, 'menuCode' => str_repeat('x', 51)]))
            ->assertSessionHasErrors(['page', 'menuCode']);

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
