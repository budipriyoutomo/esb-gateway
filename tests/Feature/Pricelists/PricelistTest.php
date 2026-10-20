<?php

namespace Tests\Feature\Pricelists;

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
use Tests\Fixtures\CorePricelist;
use Tests\TestCase;

/**
 * ESB Core pricelists, read-only ([ESB-027]).
 */
class PricelistTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://stg7.esb.co.id/core-int/pricelist';

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

        $this->get(route('pricelists.index'))->assertRedirect(route('login'));

        foreach ([
            route('pricelists.index'),
            route('pricelists.temp.index'),
            route('pricelists.temp.show', CorePricelist::TEMP_NUMBER),
        ] as $url) {
            $this->actingAs($viewer)->get($url)->assertForbidden();
        }

        Http::assertNothingSent();
    }

    // ================================================================= active

    #[Test]
    public function active_prices_are_listed_and_paged(): void
    {
        Http::fake([self::BASE.'*' => Http::response(CorePricelist::listResponse(count: 45))]);

        $this->actingAs($this->operator())
            ->get(route('pricelists.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('pricelists/index')
                ->where('entries.0.productName', 'Beras Premium')
                ->where('entries.0.price', 14500)
                ->where('entries.0.branches.all', true)
                ->where('pagination.lastPage', 3)
                ->where('filters', ['page' => 1, 'limit' => 20]));

        Http::assertSent(fn (Request $request) => $request->url() === self::BASE.'?page=1&limit=20');
        $this->assertSame('pricelist.list', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function the_active_list_can_be_searched(): void
    {
        Http::fake([self::BASE.'*' => Http::response(CorePricelist::listResponse())]);

        $this->actingAs($this->operator())
            ->get(route('pricelists.index', ['page' => 2, 'search' => 'beras', 'flagActive' => '']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters', ['page' => 2, 'limit' => 20, 'search' => 'beras']));

        Http::assertSent(fn (Request $request) => $request->url() === self::BASE.'?page=2&limit=20&indexSearch=beras');
    }

    // ============================================================== temporary

    #[Test]
    public function temporary_pricelists_are_listed_and_filtered_by_status(): void
    {
        Http::fake([self::BASE.'/temp*' => Http::response(CorePricelist::tempListResponse())]);

        $this->actingAs($this->operator())
            ->get(route('pricelists.temp.index', ['statusId' => 38, 'search' => 'pangan']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('pricelists/temp-index')
                ->where('pricelists.0.number', CorePricelist::TEMP_NUMBER)
                ->where('pricelists.0.statusName', 'Waiting For Approval')
                ->where('pricelists.0.branches.names', ['Jakarta Pusat'])
                ->where('statuses', [38 => 'Waiting For Approval', 2 => 'Rejected'])
                ->where('filters', ['page' => 1, 'limit' => 20, 'search' => 'pangan', 'statusId' => 38]));

        Http::assertSent(fn (Request $request) => $request->url()
            === self::BASE.'/temp?page=1&limit=20&indexSearch=pangan&statusID=38');
        $this->assertSame('pricelist.temp-list', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function an_undocumented_status_is_refused(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('pricelists.temp.index', ['statusId' => 5]))
            ->assertSessionHasErrors('statusId');

        Http::assertNothingSent();
    }

    #[Test]
    public function a_temporary_pricelist_is_read_by_number(): void
    {
        $url = self::BASE.'/temp/'.CorePricelist::TEMP_NUMBER;
        Http::fake([$url => Http::response(CorePricelist::tempDetailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('pricelists.temp.show', CorePricelist::TEMP_NUMBER))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('pricelists/temp-show')
                ->where('pricelist.number', CorePricelist::TEMP_NUMBER)
                ->where('pricelist.lines.0.lastPrice', 14500));

        Http::assertSent(fn (Request $request) => $request->url() === $url);
        $this->assertSame('pricelist.temp-view', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function an_unknown_temporary_pricelist_is_a_404(): void
    {
        Http::fake(['*' => Http::response(CorePricelist::notFoundResponse('/pricelist/temp/TPL1'), 400)]);

        $this->actingAs($this->operator())->get(route('pricelists.temp.show', 'TPL1'))->assertNotFound();
        Http::assertSentCount(1);
    }

    #[Test]
    public function a_number_that_could_reach_another_vendor_path_never_reaches_the_esb(): void
    {
        Http::fake();

        // /pricelist/temp/export is a literal vendor path.
        foreach (['export', 'Export', 'abc', rawurlencode('1/2'), '..', str_repeat('A1', 33)] as $number) {
            $this->actingAs($this->operator())->get('/pricelists/temp/'.$number)->assertNotFound();
        }

        Http::assertNothingSent();
    }
}
