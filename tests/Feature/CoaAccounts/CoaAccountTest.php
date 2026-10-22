<?php

namespace Tests\Feature\CoaAccounts;

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
use Tests\Fixtures\CoreCoaAccount;
use Tests\TestCase;

/**
 * ESB Core chart of accounts, read-only ([ESB-025]).
 */
class CoaAccountTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://stg7.esb.co.id/core-int/accounting/coa';

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

    #[Test]
    public function reading_needs_esb_invoke(): void
    {
        Http::fake();
        $viewer = User::factory()->create();
        $viewer->assignRole(Role::Viewer->value);

        $this->get(route('coa-accounts.index'))->assertRedirect(route('login'));
        $this->actingAs($viewer)->get(route('coa-accounts.index'))->assertForbidden();
        $this->actingAs($viewer)->get(route('coa-accounts.show', '1101'))->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function the_chart_is_listed_in_order_from_one_upstream_call(): void
    {
        Http::fake([self::BASE => Http::response(CoreCoaAccount::listResponse())]);

        $this->actingAs($this->operator())
            ->get(route('coa-accounts.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('coa-accounts/index')
                ->where('accounts.0.coaNo', '1')
                ->where('accounts.2.coaNo', '1101')
                ->where('accounts.2.level', 4)
                ->where('pagination.count', 5)
                ->where('pagination.lastPage', 1)
                ->where('filters', ['page' => 1]));

        Http::assertSent(fn (Request $request) => $request->method() === 'GET' && $request->url() === self::BASE);
        $this->assertSame('coa.list', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function the_chart_is_searched_and_filtered_by_level_in_the_gateway(): void
    {
        Http::fake([self::BASE => Http::response(CoreCoaAccount::listResponse())]);

        $this->actingAs($this->operator())
            ->get(route('coa-accounts.index', ['search' => 'biaya', 'level' => 4]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('accounts', 1)
                ->where('accounts.0.coaNo', '6101')
                ->where('filters', ['page' => 1, 'search' => 'biaya', 'level' => 4]));

        $this->actingAs($this->operator())
            ->get(route('coa-accounts.index', ['search' => '11']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                // Number prefix: 11 and 1101, not 6101.
                ->where('accounts.0.coaNo', '11')
                ->where('accounts.1.coaNo', '1101')
                ->has('accounts', 2));

        // Nothing is sent upstream but the bare list.
        Http::assertSent(fn (Request $request) => $request->url() === self::BASE);
    }

    #[Test]
    public function the_chart_is_paged_in_the_gateway(): void
    {
        $rows = array_map(
            fn (int $i) => CoreCoaAccount::row((string) (1000 + $i), "Account {$i}", 4, $i),
            range(1, 60),
        );
        Http::fake([self::BASE => Http::response(CoreCoaAccount::listResponse($rows))]);

        $this->actingAs($this->operator())
            ->get(route('coa-accounts.index', ['page' => 2]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('accounts', 10)
                ->where('accounts.0.coaNo', '1051')
                ->where('pagination.page', 2)
                ->where('pagination.lastPage', 2)
                ->where('pagination.hasMorePages', false));
    }

    #[Test]
    public function bad_filters_are_refused_before_reaching_the_esb(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('coa-accounts.index', ['page' => 0, 'level' => 5, 'search' => str_repeat('x', 256)]))
            ->assertSessionHasErrors(['page', 'level', 'search']);

        Http::assertNothingSent();
    }

    #[Test]
    public function the_detail_is_read_by_account_number(): void
    {
        Http::fake([self::BASE.'/1101' => Http::response(CoreCoaAccount::detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('coa-accounts.show', '1101'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('coa-accounts/show')
                ->where('account.coaNo', '1101')
                ->where('account.mappings.0.key', 'CASH'));

        $this->assertSame('coa.view', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function an_unknown_account_is_a_404(): void
    {
        Http::fake(['*' => Http::response(CoreCoaAccount::notFoundResponse('/accounting/coa/9999'), 400)]);

        $this->actingAs($this->operator())->get(route('coa-accounts.show', '9999'))->assertNotFound();
        Http::assertSentCount(1);
    }

    #[Test]
    public function a_successful_response_without_an_account_is_a_404(): void
    {
        Http::fake(['*' => Http::response(CoreCoaAccount::detailResponse(['coaNo' => '']))]);

        $this->actingAs($this->operator())->get(route('coa-accounts.show', '1101'))->assertNotFound();
    }

    #[Test]
    public function a_number_that_could_reach_another_vendor_path_never_reaches_the_esb(): void
    {
        Http::fake();

        // index / tax are literal vendor paths under /accounting/coa. The
        // gateway's /coa-accounts/export is its own CSV of the list (ADR-031),
        // never the vendor's /accounting/coa/export.
        foreach (['index', 'tax', 'cash-bank', rawurlencode('1/2'), '..', '-1', str_repeat('9', 33)] as $coaNo) {
            $this->actingAs($this->operator())->get('/coa-accounts/'.$coaNo)->assertNotFound();
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function an_account_number_with_dots_reaches_the_esb_encoded(): void
    {
        Http::fake(['*' => Http::response(CoreCoaAccount::detailResponse(['coaNo' => '1.1.01']))]);

        $this->actingAs($this->operator())->get('/coa-accounts/1.1.01')->assertOk();

        Http::assertSent(fn (Request $request) => $request->url() === self::BASE.'/1.1.01');
    }
}
