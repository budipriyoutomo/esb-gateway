<?php

namespace Tests\Feature\Dashboard;

use App\Authorization\Permission;
use App\Authorization\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreAccounting;
use Tests\Fixtures\CoreDisbursement;
use Tests\Fixtures\CoreInventory;
use Tests\Fixtures\CorePricelist;
use Tests\TestCase;

/**
 * The dashboard's "waiting for approval" card (TODO-modules.md UI-4): one
 * count per module whose list filters by the documented status 38 (Waiting
 * For Approval), only for modules the person may approve, read after the
 * page has rendered, and a count the ERP would not give shows as unknown
 * rather than failing the dashboard.
 */
class PendingApprovalsTest extends TestCase
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

    /**
     * @param  list<string>  $sent  Filled with "PATH?QUERY" of every request.
     * @param  list<string>  $failing  Paths that answer with a server error.
     */
    private function fakeUpstream(array &$sent = [], array $failing = []): void
    {
        Http::fake(function (Request $request) use (&$sent, $failing) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);
            $sent[] = $path.'?'.(string) parse_url($request->url(), PHP_URL_QUERY);

            foreach ($failing as $failingPath) {
                if (str_ends_with($path, $failingPath)) {
                    return Http::response(['message' => 'boom'], 500);
                }
            }

            return match (true) {
                str_ends_with($path, '/finance/disbursement') => Http::response(CoreDisbursement::listResponse([CoreDisbursement::row()], count: 4)),
                str_ends_with($path, '/accounting/memorial-journal') => Http::response(CoreAccounting::mjList([CoreAccounting::mjRow()], count: 2)),
                str_ends_with($path, '/inventory/item-journal') => Http::response(CoreInventory::listResponse('/inventory/item-journal', [], count: 0)),
                str_ends_with($path, '/pricelist/temp') => Http::response(CorePricelist::tempListResponse(count: 7)),
                default => Http::response(['message' => 'unexpected'], 404),
            };
        });
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SuperAdmin->value);

        return $user;
    }

    #[Test]
    public function the_counts_are_deferred_so_the_dashboard_renders_without_calling_the_erp(): void
    {
        $sent = [];
        $this->fakeUpstream($sent);

        $this->actingAs($this->superAdmin())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('dashboard')
                ->missing('pendingApprovals'));

        $this->assertSame([], $sent);
    }

    #[Test]
    public function an_approver_sees_one_count_per_module_from_the_waiting_for_approval_status(): void
    {
        $sent = [];
        $this->fakeUpstream($sent);

        $this->actingAs($this->superAdmin())
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->loadDeferredProps(fn (AssertableInertia $reload) => $reload
                    ->has('pendingApprovals', 4)
                    ->where('pendingApprovals.0', [
                        'key' => 'disbursements',
                        'title' => 'Disbursements',
                        'count' => 4,
                        'href' => '/disbursements?statusId=38',
                    ])
                    ->where('pendingApprovals.1.key', 'memorial-journals')
                    ->where('pendingApprovals.1.count', 2)
                    ->where('pendingApprovals.1.href', '/memorial-journals?statusId=38')
                    ->where('pendingApprovals.2.key', 'item-journals')
                    ->where('pendingApprovals.2.count', 0)
                    ->where('pendingApprovals.2.href', '/item-journals?statusId=38')
                    ->where('pendingApprovals.3.key', 'pricelists')
                    ->where('pendingApprovals.3.title', 'Proposed prices')
                    ->where('pendingApprovals.3.count', 7)
                    ->where('pendingApprovals.3.href', '/pricelists/temp?statusId=38')));

        $this->assertCount(4, $sent);

        foreach ($sent as $request) {
            parse_str((string) parse_url('http://x'.$request, PHP_URL_QUERY), $query);
            $this->assertSame('38', $query['statusID'] ?? null, $request);
            $this->assertSame('1', $query['limit'] ?? null, $request);
        }
    }

    #[Test]
    public function only_the_modules_the_person_may_approve_and_read_are_counted(): void
    {
        $sent = [];
        $this->fakeUpstream($sent);

        $user = User::factory()->create();
        $user->assignRole(Role::IntegrationOperator->value);
        $user->givePermissionTo(Permission::ApproveDisbursements->value);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->loadDeferredProps(fn (AssertableInertia $reload) => $reload
                    ->has('pendingApprovals', 1)
                    ->where('pendingApprovals.0.key', 'disbursements')));

        $this->assertCount(1, $sent);
        $this->assertStringContainsString('/finance/disbursement?', $sent[0]);
    }

    #[Test]
    public function approving_without_esb_invoke_counts_nothing(): void
    {
        $sent = [];
        $this->fakeUpstream($sent);

        $user = User::factory()->create();
        $user->assignRole(Role::Viewer->value);
        $user->givePermissionTo(Permission::ApproveDisbursements->value);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->loadDeferredProps(fn (AssertableInertia $reload) => $reload
                    ->where('pendingApprovals', [])));

        $this->assertSame([], $sent);
    }

    #[Test]
    public function a_count_the_erp_will_not_give_is_unknown_and_the_others_still_show(): void
    {
        $this->fakeUpstream(failing: ['/accounting/memorial-journal']);

        $this->actingAs($this->superAdmin())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->loadDeferredProps(fn (AssertableInertia $reload) => $reload
                    ->has('pendingApprovals', 4)
                    ->where('pendingApprovals.0.count', 4)
                    ->where('pendingApprovals.1.key', 'memorial-journals')
                    ->where('pendingApprovals.1.count', null)));
    }
}
