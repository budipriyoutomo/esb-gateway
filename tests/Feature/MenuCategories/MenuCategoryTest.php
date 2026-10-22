<?php

namespace Tests\Feature\MenuCategories;

use App\Authorization\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\OmsMenuCategory;
use Tests\TestCase;

/**
 * POS menu categories, ESB OMS ([ESB-047], S1): esb.invoke, the vendor's
 * page parameter, no ESB URLs in the page. There is no detail operation:
 * one category is found on the page it was listed on, else on the others,
 * up to a bound.
 */
class MenuCategoryTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://stg7.esb.co.id/api-fnb-backend-int/web/corev1/master/get-menu-category';

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

    private function operator(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::IntegrationOperator->value);

        return $user;
    }

    private static function page(Request $request): int
    {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return (int) ($query['page'] ?? 0);
    }

    #[Test]
    public function reading_needs_esb_invoke(): void
    {
        Http::fake();

        $this->get(route('menu-categories.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('menu-categories.index'))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('menu-categories.show', OmsMenuCategory::ID))->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function the_list_shows_a_page_of_categories_without_the_esbs_urls(): void
    {
        Http::fake(['*' => Http::response(OmsMenuCategory::list([OmsMenuCategory::row()], page: 2))]);

        $this->actingAs($this->operator())
            ->get(route('menu-categories.index', ['page' => 2]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('menu-categories/index')
                ->where('categories.0.id', OmsMenuCategory::ID)
                ->where('categories.0.details.0.name', 'Main Course-Int')
                ->where('pagination.count', 16)
                ->where('pagination.page', 2)
                ->where('filters.page', 2))
            ->assertDontSee('api-fnb-backend-int');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === self::URL.'?page=2');
    }

    #[Test]
    public function a_bad_page_never_reaches_the_esb(): void
    {
        Http::fake();

        foreach ([['page' => 0], ['page' => 'two'], ['page' => 10001]] as $query) {
            $this->actingAs($this->operator())->get(route('menu-categories.index', $query))->assertSessionHasErrors('page');
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function a_category_is_read_from_the_page_it_was_listed_on(): void
    {
        Http::fake(['*' => Http::response(OmsMenuCategory::list([OmsMenuCategory::row()], page: 2))]);

        $this->actingAs($this->operator())
            ->get(route('menu-categories.show', ['id' => OmsMenuCategory::ID, 'page' => 2]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('menu-categories/show')
                ->where('category.name', 'Food-Int')
                ->where('page', 2));

        Http::assertSentCount(1);
    }

    #[Test]
    public function a_category_not_on_that_page_is_looked_for_on_the_others(): void
    {
        $pages = [];
        Http::fake(function (Request $request) use (&$pages) {
            $page = self::page($request);
            $pages[] = $page;

            return Http::response(OmsMenuCategory::list(
                $page === 3 ? [OmsMenuCategory::row()] : [OmsMenuCategory::row(['menuCategoryID' => 90 + $page])],
                page: $page,
                count: 25,
            ));
        });

        $this->actingAs($this->operator())
            ->get(route('menu-categories.show', ['id' => OmsMenuCategory::ID, 'page' => 2]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('page', 3));

        $this->assertSame([2, 1, 3], $pages);
    }

    #[Test]
    public function an_unknown_category_is_a_404_after_a_bounded_search(): void
    {
        $pages = 0;
        Http::fake(function (Request $request) use (&$pages) {
            $pages++;

            return Http::response(OmsMenuCategory::list([OmsMenuCategory::row(['menuCategoryID' => 1])], page: self::page($request), count: 1000));
        });

        $this->actingAs($this->operator())->get(route('menu-categories.show', 999))->assertNotFound();

        $this->assertSame(20, $pages);
        $this->actingAs($this->operator())->get('/menu-categories/0')->assertNotFound();
    }
}
