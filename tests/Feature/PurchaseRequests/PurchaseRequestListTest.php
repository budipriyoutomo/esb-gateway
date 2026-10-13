<?php

namespace Tests\Feature\PurchaseRequests;

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
 * GET /purchase/purchase-request, end to end: route, authorization,
 * validation, adapter, mapper, and Inertia page.
 *
 * Read-only by design. The write operations in this group (create, update,
 * delete, authorize, reject) are not idempotent and arrive in a later slice.
 */
class PurchaseRequestListTest extends TestCase
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
    private function listResponse(int $count = 45): array
    {
        return [
            'path' => 'https://stg7.esb.co.id/core-int/purchase/purchase-request',
            'timestamp' => '2026-03-26 10:30:00',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => [
                'page' => 1,
                'limit' => 20,
                'count' => $count,
                'data' => [
                    [
                        'additionalInfo' => 'Restock bar',
                        'branchID' => 3,
                        'branchName' => 'Outlet Senopati',
                        'createdDate' => '2026-03-26 09:15:00',
                        'purchaseRequestDate' => '2026-03-26',
                        'purchaseRequestNum' => 'PR202603260005',
                        'requestTemplateID' => 7,
                        'requestTemplateName' => 'Weekly bar',
                        'requiredDate' => '2026-03-28',
                        'statusID' => 2,
                        'statusName' => 'Authorized',
                    ],
                ],
                'prev' => '',
                'next' => 'https://stg7.esb.co.id/core-int/purchase/purchase-request?page=2&limit=20',
            ],
            'errors' => null,
        ];
    }

    // ---------------------------------------------------------------- access

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('purchase-requests.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function a_viewer_may_not_read_erp_data_through_the_gateway(): void
    {
        Http::fake();

        $this->actingAs($this->userWithRole(Role::Viewer))
            ->get(route('purchase-requests.index'))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function an_it_admin_may_not_invoke_esb_operations(): void
    {
        Http::fake();

        $this->actingAs($this->userWithRole(Role::ItAdmin))
            ->get(route('purchase-requests.index'))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function an_integration_operator_can_list_purchase_requests(): void
    {
        Http::fake(['*' => Http::response($this->listResponse())]);

        $this->actingAs($this->operator())
            ->get(route('purchase-requests.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('purchase-requests/index')
                ->has('purchaseRequests', 1)
                ->where('purchaseRequests.0.number', 'PR202603260005')
                ->where('purchaseRequests.0.branchName', 'Outlet Senopati')
                ->where('purchaseRequests.0.statusName', 'Authorized'),
            );
    }

    #[Test]
    public function it_calls_the_documented_core_path(): void
    {
        Http::fake(['*' => Http::response($this->listResponse())]);

        $this->actingAs($this->operator())->get(route('purchase-requests.index'))->assertOk();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://stg7.esb.co.id/core-int/purchase/purchase-request?'));
    }

    // --------------------------------------------------------------- filters

    #[Test]
    public function it_translates_filters_into_the_vendors_parameter_names(): void
    {
        Http::fake(['*' => Http::response($this->listResponse())]);

        $this->actingAs($this->operator())
            ->get(route('purchase-requests.index', [
                'page' => 2,
                'limit' => 50,
                'number' => 'PR2026',
                'dateFrom' => '2026-03-01',
                'dateTo' => '2026-03-31',
                'requiredFrom' => '2026-03-02',
                'requiredTo' => '2026-04-01',
                'branchIds' => [3, 8],
                'search' => 'bar',
            ]))
            ->assertOk();

        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $query === [
                'page' => '2',
                'limit' => '50',
                'purchaseRequestNum' => 'PR2026',
                'purchaseRequestDateFrom' => '2026-03-01',
                'purchaseRequestDateTo' => '2026-03-31',
                'requiredDateFrom' => '2026-03-02',
                'requiredDateTo' => '2026-04-01',
                // The vendor documents branchIDs as a comma-separated string.
                'branchIDs' => '3,8',
                'indexSearch' => 'bar',
            ];
        });
    }

    #[Test]
    public function it_sends_only_the_filters_that_were_supplied(): void
    {
        Http::fake(['*' => Http::response($this->listResponse())]);

        $this->actingAs($this->operator())->get(route('purchase-requests.index'))->assertOk();

        // Sending empty filters would make the ESB narrow the result set to
        // rows matching an empty string. Sort is left to the vendor's
        // documented default, since its accepted columns are not documented.
        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $query === ['page' => '1', 'limit' => '20'];
        });
    }

    #[Test]
    public function the_filters_are_echoed_back_to_the_page(): void
    {
        Http::fake(['*' => Http::response($this->listResponse())]);

        $this->actingAs($this->operator())
            ->get(route('purchase-requests.index', ['search' => 'bar', 'dateFrom' => '2026-03-01']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters.search', 'bar')
                ->where('filters.dateFrom', '2026-03-01'),
            );
    }

    // ------------------------------------------------------------ pagination

    #[Test]
    public function the_page_never_receives_the_upstream_cursor_urls(): void
    {
        Http::fake(['*' => Http::response($this->listResponse())]);

        $response = $this->actingAs($this->operator())->get(route('purchase-requests.index'));

        $response->assertDontSee('esb.co.id');
        $response->assertDontSee('stg7');
    }

    #[Test]
    public function pagination_is_re_derived_on_our_side(): void
    {
        Http::fake(['*' => Http::response($this->listResponse(count: 45))]);

        $this->actingAs($this->operator())
            ->get(route('purchase-requests.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pagination.count', 45)
                ->where('pagination.lastPage', 3)
                ->where('pagination.hasMorePages', true),
            );
    }

    // ------------------------------------------------------------ validation

    #[Test]
    public function a_limit_above_the_esb_cap_is_rejected_before_the_call(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('purchase-requests.index', ['limit' => 101]))
            ->assertSessionHasErrors('limit');

        Http::assertNothingSent();
    }

    #[Test]
    public function a_non_numeric_page_is_rejected_before_the_call(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('purchase-requests.index', ['page' => 'abc']))
            ->assertSessionHasErrors('page');

        Http::assertNothingSent();
    }

    #[Test]
    public function a_date_not_in_the_documented_format_is_rejected_before_the_call(): void
    {
        Http::fake();

        // The vendor documents these as YYYY-MM-DD.
        $this->actingAs($this->operator())
            ->get(route('purchase-requests.index', ['dateFrom' => '26/03/2026']))
            ->assertSessionHasErrors('dateFrom');

        Http::assertNothingSent();
    }

    #[Test]
    public function a_date_range_that_ends_before_it_starts_is_rejected(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('purchase-requests.index', ['dateFrom' => '2026-03-31', 'dateTo' => '2026-03-01']))
            ->assertSessionHasErrors('dateTo');

        $this->actingAs($this->operator())
            ->get(route('purchase-requests.index', ['requiredFrom' => '2026-03-31', 'requiredTo' => '2026-03-01']))
            ->assertSessionHasErrors('requiredTo');

        Http::assertNothingSent();
    }

    #[Test]
    public function branch_ids_must_be_positive_integers(): void
    {
        Http::fake();

        // They are joined with commas for the ESB, so anything else here could
        // smuggle extra values into that list.
        $this->actingAs($this->operator())
            ->get(route('purchase-requests.index', ['branchIds' => ['3,4']]))
            ->assertSessionHasErrors('branchIds.0');

        Http::assertNothingSent();
    }

    // --------------------------------------------------------- upstream fail

    #[Test]
    public function an_upstream_failure_is_shown_safely(): void
    {
        Http::fake(['*' => Http::response([
            'path' => 'https://stg7.esb.co.id/core-int/purchase/purchase-request',
            'timestamp' => '2026-03-26 10:30:00',
            'status' => 'fail',
            'code' => 'EC03100032',
            'message' => 'strconv.ParseInt: parsing "x": invalid syntax',
            'result' => null,
            'errors' => null,
        ], 500)]);

        $response = $this->actingAs($this->operator())->get(route('purchase-requests.index'));

        $response->assertStatus(502);
        $response->assertDontSee('strconv');
    }

    #[Test]
    public function a_timeout_is_reported_as_a_gateway_timeout(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));

        $this->actingAs($this->operator())
            ->get(route('purchase-requests.index'))
            ->assertStatus(504);
    }

    // ----------------------------------------------------------- observability

    #[Test]
    public function the_call_is_recorded_in_the_integration_log_against_the_user(): void
    {
        Http::fake(['*' => Http::response($this->listResponse())]);

        $operator = $this->operator();

        $this->actingAs($operator)->get(route('purchase-requests.index'))->assertOk();

        $log = IntegrationLog::query()->sole();

        $this->assertSame('purchase-request.list', $log->operation);
        $this->assertSame('success', $log->outcome);
        $this->assertSame($operator->id, $log->user_id);
    }
}
