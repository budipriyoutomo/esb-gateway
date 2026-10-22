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
 * Menu extras across menus, read-only (TODO-modules §6.3). The catalog has no
 * extra endpoint, so this flattens the extras of one Get Menu page
 * ([ESB-013]) -- one ESB call per page, nothing aggregated across pages.
 */
class MasterMenuExtrasTest extends TestCase
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

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('master-menus.extras'))->assertRedirect(route('login'));
    }

    #[Test]
    public function reading_extras_needs_esb_invoke(): void
    {
        Http::fake();

        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('master-menus.extras'))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function the_extras_of_a_menu_page_are_listed_with_their_menu(): void
    {
        Http::fake([self::URL.'*' => Http::response(OmsGetMenu::response([
            OmsGetMenu::row(),
            OmsGetMenu::row(['menuCode' => 'NX01', 'menuName' => 'No Extras', 'menuExtras' => []]),
        ], count: 45))]);

        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->get(route('master-menus.extras'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('master-menus/extras')
                ->has('extras', 1)
                ->where('extras.0.menuCode', 'PS01')
                ->where('extras.0.menuName', 'Paket Separate')
                ->where('extras.0.name', 'Beef Burger')
                ->where('extras.0.price', 1000)
                ->where('extras.0.maxQty', 10)
                ->where('pagination.page', 1)
                ->where('pagination.count', 45)
                ->where('filters', ['page' => 1]));

        Http::assertSent(fn (Request $request) => $request->url() === self::URL.'?page=1');
    }

    #[Test]
    public function the_extras_can_be_filtered_by_menu_code_and_paged(): void
    {
        Http::fake([self::URL.'*' => Http::response(OmsGetMenu::response())]);

        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->get(route('master-menus.extras', ['menuCode' => 'PS01', 'page' => 2]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters', ['page' => 2, 'menuCode' => 'PS01']));

        Http::assertSent(fn (Request $request) => $request->url() === self::URL.'?page=2&menuCode=PS01');
    }

    #[Test]
    public function bad_filters_are_refused_before_reaching_the_esb(): void
    {
        Http::fake();

        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->get(route('master-menus.extras', ['page' => 0]))
            ->assertSessionHasErrors('page');

        Http::assertNothingSent();
    }
}
