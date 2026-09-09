<?php

namespace Tests\Feature\Products;

use App\Authorization\Role;
use App\Models\IntegrationLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The first ESB vertical slice, end to end: route, authorization, validation,
 * adapter, mapper, and Inertia page.
 *
 * GET /product/list is the vendor's own tutorial endpoint, which makes it the
 * cheapest place to prove the pattern every later adapter copies.
 */
class ProductListTest extends TestCase
{
    use RefreshDatabase;

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

    /**
     * @return array<string, mixed>
     */
    private function productListResponse(int $count = 92): array
    {
        return [
            'path' => 'https://stg7.esb.co.id/core-int/product/list',
            'timestamp' => '2024-05-28 18:20:51',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => [
                'page' => 1,
                'limit' => 20,
                'count' => $count,
                'data' => [
                    [
                        'productID' => 75,
                        'productName' => 'Air mineral',
                        'productCode' => 'Air_mineral',
                        'categoryID' => 14,
                        'categoryName' => 'Kategori Test',
                        'subCategoryID' => 10,
                        'subCategoryName' => 'BUDGET INV',
                        'bomID' => null,
                        'bomName' => null,
                        'categoryTypeID' => 1,
                        'categoryTypeName' => 'Inventory',
                        'flagActive' => 1,
                    ],
                ],
                'prev' => '',
                'next' => 'https://stg7.esb.co.id/core-int/product/list?page=2&limit=20',
            ],
            'errors' => null,
        ];
    }

    // ---------------------------------------------------------------- access

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('products.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function a_viewer_may_not_read_erp_data_through_the_gateway(): void
    {
        // Reading product master data means sending an operation to the ESB,
        // which is the esb.invoke permission -- deliberately separate from the
        // read-only permissions on gateway-owned screens
        // (docs/06-authentication-authorization.md).
        Http::fake();

        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('products.index'))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function an_it_admin_may_not_invoke_esb_operations(): void
    {
        Http::fake();

        $this->actingAs($this->userWithRole(Role::ItAdmin))
            ->get(route('products.index'))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function an_integration_operator_can_list_products(): void
    {
        Http::fake(['*' => Http::response($this->productListResponse())]);

        $this->actingAs($this->operator())
            ->get(route('products.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('products/index')
                ->has('products', 1)
                ->where('products.0.name', 'Air mineral')
                ->where('products.0.code', 'Air_mineral')
                ->where('products.0.isActive', true),
            );
    }

    // ------------------------------------------------------------ pagination

    #[Test]
    public function it_forwards_pagination_and_filters_to_the_esb(): void
    {
        Http::fake(['*' => Http::response($this->productListResponse())]);

        $this->actingAs($this->operator())
            ->get(route('products.index', ['page' => 2, 'limit' => 50, 'productName' => 'mineral']))
            ->assertOk();

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'page=2')
            && str_contains($request->url(), 'limit=50')
            && str_contains($request->url(), 'productName=mineral'));
    }

    #[Test]
    public function it_sends_only_the_filters_that_were_supplied(): void
    {
        Http::fake(['*' => Http::response($this->productListResponse())]);

        $this->actingAs($this->operator())->get(route('products.index'))->assertOk();

        // Sending empty filters would make the ESB narrow the result set to
        // rows matching an empty string.
        Http::assertSent(fn (Request $request): bool => ! str_contains($request->url(), 'productName=')
            && ! str_contains($request->url(), 'sort='));
    }

    #[Test]
    public function the_page_never_receives_the_upstream_cursor_urls(): void
    {
        Http::fake(['*' => Http::response($this->productListResponse())]);

        $response = $this->actingAs($this->operator())->get(route('products.index'));

        // The ESB returns absolute prev/next URLs carrying its own host.
        // Leaking those to the browser would advertise the ESB and invite
        // direct browser-to-ESB calls, which ADR-006 forbids.
        $response->assertDontSee('esb.co.id');
        $response->assertDontSee('stg7');
    }

    #[Test]
    public function pagination_is_re_derived_on_our_side(): void
    {
        Http::fake(['*' => Http::response($this->productListResponse(count: 92))]);

        $this->actingAs($this->operator())
            ->get(route('products.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pagination.page', 1)
                ->where('pagination.limit', 20)
                ->where('pagination.count', 92)
                ->where('pagination.lastPage', 5)
                ->where('pagination.hasMorePages', true),
            );
    }

    // ------------------------------------------------------------ validation

    #[Test]
    public function a_limit_above_the_esb_cap_is_rejected_before_the_call(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('products.index', ['limit' => 101]))
            ->assertSessionHasErrors('limit');

        // The ESB enforces this with EC03100040, but catching it here saves a
        // round trip and gives a message that names the field.
        Http::assertNothingSent();
    }

    #[Test]
    public function a_page_below_one_is_rejected_before_the_call(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('products.index', ['page' => 0]))
            ->assertSessionHasErrors('page');

        Http::assertNothingSent();
    }

    #[Test]
    public function a_non_numeric_page_is_rejected_before_the_call(): void
    {
        Http::fake();

        // The ESB answers this with EC03100032 "Unknown Error" and a Go
        // strconv message, which tells the user nothing. Validating here turns
        // it into a field error.
        $this->actingAs($this->operator())
            ->get(route('products.index', ['page' => 'abc']))
            ->assertSessionHasErrors('page');

        Http::assertNothingSent();
    }

    #[Test]
    public function an_unsupported_sort_column_is_rejected_before_the_call(): void
    {
        Http::fake();

        // Also EC03100032 upstream -- the ESB does not distinguish it from any
        // other unknown error.
        $this->actingAs($this->operator())
            ->get(route('products.index', ['sort' => 'productPrice']))
            ->assertSessionHasErrors('sort');

        Http::assertNothingSent();
    }

    #[Test]
    public function a_documented_sort_column_is_accepted(): void
    {
        Http::fake(['*' => Http::response($this->productListResponse())]);

        $this->actingAs($this->operator())
            ->get(route('products.index', ['sort' => 'productName']))
            ->assertOk();

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'sort=productName'));
    }

    // --------------------------------------------------------- upstream fail

    #[Test]
    public function an_upstream_failure_is_shown_safely_and_not_as_a_stack_trace(): void
    {
        Http::fake(['*' => Http::response([
            'path' => 'https://stg7.esb.co.id/core-int/product/list',
            'timestamp' => '2024-05-28 18:20:51',
            'status' => 'fail',
            'code' => 'EC0118',
            'message' => 'Undefined index: menuTemplateID in /var/www/esb/app/Controller.php line 220',
            'result' => null,
            'errors' => null,
        ])]);

        $response = $this->actingAs($this->operator())->get(route('products.index'));

        $response->assertStatus(502);
        $response->assertDontSee('/var/www');
        $response->assertDontSee('menuTemplateID');
    }

    #[Test]
    public function a_timeout_is_reported_as_a_gateway_timeout(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));

        $this->actingAs($this->operator())
            ->get(route('products.index'))
            ->assertStatus(504);
    }

    #[Test]
    public function an_esb_authorization_failure_is_not_shown_as_the_users_own(): void
    {
        Http::fake(['*' => Http::response([
            'path' => 'https://stg7.esb.co.id/core-int/product/list',
            'timestamp' => '2024-05-28 18:20:51',
            'status' => 'fail',
            'code' => 'EC03100002',
            'message' => 'you did not have access to this resource',
            'result' => null,
            'errors' => null,
        ])]);

        // The gateway's ESB credential lacks the privilege -- that is a
        // configuration problem upstream, not this user being unauthorized.
        // Returning 403 here would send them to ask for a role they do not
        // need.
        $this->actingAs($this->operator())
            ->get(route('products.index'))
            ->assertStatus(502);
    }

    // ----------------------------------------------------------- observability

    #[Test]
    public function the_call_is_recorded_in_the_integration_log_against_the_user(): void
    {
        Http::fake(['*' => Http::response($this->productListResponse())]);

        $operator = $this->operator();

        $this->actingAs($operator)->get(route('products.index'))->assertOk();

        $log = IntegrationLog::query()->sole();

        $this->assertSame('product.list', $log->operation);
        $this->assertSame('success', $log->outcome);
        $this->assertSame($operator->id, $log->user_id);
    }

    #[Test]
    public function a_failed_call_is_recorded_too(): void
    {
        Http::fake(['*' => Http::response([
            'code' => 'EC03100003',
            'message' => 'Validation Error',
            'status' => 'fail',
            'result' => null,
        ])]);

        $this->actingAs($this->operator())->get(route('products.index'));

        $this->assertSame('validation', IntegrationLog::query()->sole()->outcome);
    }
}
