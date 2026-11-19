<?php

namespace Tests\Feature\Branches;

use App\Authorization\Role;
use App\Models\IntegrationLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreBranch;
use Tests\TestCase;

/**
 * ESB Core Branch, read-only: GET /branch (whole list, searched and paged by
 * the gateway) and GET /branch/{branchID} ([ESB-051]).
 */
class BranchTest extends TestCase
{
    use RefreshDatabase;

    private const LIST_URL = 'https://stg7.esb.co.id/core-int/branch';

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
     * @return list<array<string, mixed>>
     */
    private function manyBranches(int $count): array
    {
        return array_map(
            static fn (int $i): array => CoreBranch::row([
                'branchID' => $i,
                'branchCode' => sprintf('OUT%02d', $i),
                'branchName' => 'Outlet '.$i,
            ]),
            range(1, $count),
        );
    }

    // ================================================================= access

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('branches.index'))->assertRedirect(route('login'));
        $this->get(route('branches.show', 12))->assertRedirect(route('login'));
    }

    #[Test]
    public function reading_branches_needs_esb_invoke(): void
    {
        Http::fake();

        foreach ([Role::Viewer, Role::ItAdmin] as $role) {
            $user = $this->userWithRole($role);

            $this->actingAs($user)->get(route('branches.index'))->assertForbidden();
            $this->actingAs($user)->get(route('branches.show', 12))->assertForbidden();
        }

        Http::assertNothingSent();
    }

    // =================================================================== list

    #[Test]
    public function an_operator_can_list_branches(): void
    {
        Http::fake([self::LIST_URL.'*' => Http::response(CoreBranch::listResponse($this->manyBranches(45)))]);

        $this->actingAs($this->operator())
            ->get(route('branches.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('branches/index')
                ->has('branches', 20)
                ->where('branches.0.id', 1)
                ->where('branches.0.code', 'OUT01')
                ->where('branches.0.name', 'Outlet 1')
                ->where('pagination.count', 45)
                ->where('pagination.lastPage', 3)
                ->where('filters', ['page' => 1, 'limit' => 20]));

        // No status filter: inactive branches are included.
        Http::assertSent(fn (Request $request) => $request->method() === 'GET'
            && $request->url() === self::LIST_URL.'?flagIncludeNotActive=1');

        $this->assertSame('branch.list', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function the_list_is_searched_and_paged_by_the_gateway(): void
    {
        Http::fake([self::LIST_URL.'*' => Http::response(CoreBranch::listResponse([
            ...$this->manyBranches(30),
            CoreBranch::row(['branchID' => 99, 'branchCode' => 'KMG', 'branchName' => 'Maharasa Kemang']),
        ]))]);

        $this->actingAs($this->operator())
            ->get(route('branches.index', ['search' => 'outlet 1', 'page' => 2, 'limit' => 5]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                // "Outlet 1", "Outlet 10" … "Outlet 19": 11 matches, page 2 of 5.
                ->has('branches', 5)
                ->where('branches.0.name', 'Outlet 14')
                ->where('pagination.count', 11)
                ->where('filters', ['page' => 2, 'limit' => 5, 'search' => 'outlet 1']));

        $this->actingAs($this->operator())
            ->get(route('branches.index', ['search' => 'kmg']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('branches', 1)
                ->where('branches.0.id', 99));
    }

    #[Test]
    public function a_status_filter_is_sent_to_the_esb(): void
    {
        Http::fake([self::LIST_URL.'*' => Http::response(CoreBranch::listResponse())]);

        $this->actingAs($this->operator())
            ->get(route('branches.index', ['flagActive' => 0]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters', ['page' => 1, 'limit' => 20, 'flagActive' => 0]));

        Http::assertSent(fn (Request $request) => $request->url() === self::LIST_URL.'?flagActive=0');
    }

    #[Test]
    public function bad_filters_are_refused_before_reaching_the_esb(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('branches.index', [
                'page' => 0,
                'limit' => 101,
                'search' => str_repeat('x', 256),
                'flagActive' => 2,
            ]))
            ->assertSessionHasErrors(['page', 'limit', 'search', 'flagActive']);

        Http::assertNothingSent();
    }

    // ================================================================= detail

    #[Test]
    public function the_detail_shows_the_branch(): void
    {
        Http::fake([self::LIST_URL.'/12' => Http::response(CoreBranch::detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('branches.show', 12))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('branches/show')
                ->where('branch.id', 12)
                ->where('branch.name', 'Maharasa Kemang')
                ->where('branch.code', 'OUT1')
                ->where('branch.active', true)
                ->where('branch.licences.cashier', 2)
                ->missing('branch.image'));

        Http::assertSent(fn (Request $request) => $request->method() === 'GET'
            && $request->url() === self::LIST_URL.'/12');

        $this->assertSame('branch.view', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function an_unknown_branch_is_a_404(): void
    {
        Http::fake(['*' => Http::response([
            'path' => self::LIST_URL.'/999',
            'timestamp' => '2026-01-15 10:30:00',
            'status' => 'fail',
            'code' => 'EC03100004',
            'message' => 'Not Found',
            'result' => null,
            'errors' => null,
        ], 400)]);

        $this->actingAs($this->operator())
            ->get(route('branches.show', 999))
            ->assertNotFound();

        Http::assertSentCount(1);
    }

    #[Test]
    public function a_successful_response_without_a_branch_is_a_404(): void
    {
        Http::fake(['*' => Http::response(CoreBranch::detailResponse(['branchID' => 0]))]);

        $this->actingAs($this->operator())
            ->get(route('branches.show', 12))
            ->assertNotFound();
    }

    #[Test]
    public function an_id_that_is_not_a_positive_number_never_reaches_the_esb(): void
    {
        Http::fake();

        foreach (['abc', '0', '-1', rawurlencode('1/2'), str_repeat('9', 11)] as $id) {
            $this->actingAs($this->operator())->get('/branches/'.$id)->assertNotFound();
        }

        Http::assertNothingSent();
    }
}
