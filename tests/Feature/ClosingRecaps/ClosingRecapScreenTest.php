<?php

namespace Tests\Feature\ClosingRecaps;

use App\Authorization\Role;
use App\Models\ClosingItemMapping;
use App\Models\ClosingOutletMapping;
use App\Models\ClosingPurposeMapping;
use App\Models\ClosingRecap;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\ClosingTotals;
use Tests\TestCase;

/**
 * The closing recap screens: what sync-api sent, held in the gateway, and
 * which mappings it still needs before it can be prepared for the ESB
 * (ADR-032). Nothing here calls the ESB.
 */
class ClosingRecapScreenTest extends TestCase
{
    use HoldsClosingRecaps;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        $this->seed(RolePermissionSeeder::class);
    }

    private function mapEverything(): void
    {
        ClosingOutletMapping::query()->create([
            'outlet_code' => 'STTSM',
            'branch_id' => 12, 'branch_code' => 'STTSM', 'branch_name' => 'Sushi Tei TSM',
            'location_id' => 31, 'location_name' => 'Gudang TSM',
        ]);
        ClosingItemMapping::query()->create([
            'source' => 'closing', 'item_key' => ClosingTotals::MENU_ID,
            'esb_menu_id' => 188, 'esb_menu_code' => 'SU001', 'esb_menu_name' => 'Sushi Roll Salmon',
            'bom_id' => 412, 'bom_name' => 'Sushi Roll Salmon',
        ]);
        ClosingItemMapping::query()->create([
            'source' => 'pos_only', 'item_key' => 'DRK-OCHA-01',
            'esb_menu_id' => 190, 'esb_menu_code' => 'OCHA', 'esb_menu_name' => 'Ocha Dingin',
        ]);
        foreach (['waste' => -1, 'adjustment' => 1] as $kind => $direction) {
            ClosingPurposeMapping::query()->create([
                'kind' => $kind, 'purpose_id' => 7, 'purpose_name' => ucfirst($kind), 'direction' => $direction,
            ]);
        }
    }

    // ============================================================ access

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $recap = $this->holdRecap();

        $this->get(route('closing-recaps.index'))->assertRedirect(route('login'));
        $this->get(route('closing-recaps.show', $recap))->assertRedirect(route('login'));
    }

    #[Test]
    public function a_viewer_cannot_see_closing_recaps(): void
    {
        $viewer = $this->userWithRole(Role::Viewer);
        $recap = $this->holdRecap();

        $this->actingAs($viewer)->get(route('closing-recaps.index'))->assertForbidden();
        $this->actingAs($viewer)->get(route('closing-recaps.show', $recap))->assertForbidden();
    }

    // ============================================================= list

    #[Test]
    public function an_operator_sees_recaps_newest_production_date_first(): void
    {
        $this->holdRecap(['productionDate' => '2026-10-11']);
        $this->holdRecap(['productionDate' => '2026-10-12', 'items' => [ClosingTotals::posOnlyItem()]]);

        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->get(route('closing-recaps.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('closing-recaps/index')
                ->has('recaps.data', 2)
                ->where('recaps.data.0.productionDate', '2026-10-12')
                ->where('recaps.data.0.outletCode', 'STTSM')
                ->where('recaps.data.0.sequence', 2)
                ->where('recaps.data.0.items', 1)
                ->where('recaps.data.0.status', ClosingRecap::STATUS_RECEIVED)
                ->where('recaps.data.0.ready', false)
                ->where('recaps.data.0.missing', 2) // the outlet and the product's menu
                ->where('recaps.data.1.productionDate', '2026-10-11')
                ->where('statuses', ClosingRecap::STATUSES));
    }

    #[Test]
    public function the_list_is_filtered_by_outlet_date_and_status(): void
    {
        $this->holdRecap();
        $this->holdRecap(['outlet' => ['code' => 'RXT']]);
        $this->holdRecap(['productionDate' => '2026-10-13']);

        $operator = $this->userWithRole(Role::IntegrationOperator);

        $this->actingAs($operator)
            ->get(route('closing-recaps.index', ['outletCode' => 'rxt']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('recaps.data', 1)
                ->where('recaps.data.0.outletCode', 'RXT')
                ->where('filters.outletCode', 'rxt'));

        $this->actingAs($operator)
            ->get(route('closing-recaps.index', ['productionDate' => '2026-10-13']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('recaps.data', 1));

        $this->actingAs($operator)
            ->get(route('closing-recaps.index', ['status' => 'nonsense']))
            ->assertSessionHasErrors('status');
    }

    #[Test]
    public function a_fully_mapped_recap_is_ready(): void
    {
        $this->holdRecap();
        $this->mapEverything();

        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->get(route('closing-recaps.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('recaps.data.0.ready', true)
                ->where('recaps.data.0.missing', 0));
    }

    // =========================================================== detail

    #[Test]
    public function the_detail_shows_items_history_and_every_missing_mapping(): void
    {
        $this->holdRecap(['sequence' => 1]);
        $recap = $this->holdRecap(['sequence' => 2]);

        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->get(route('closing-recaps.show', $recap))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('closing-recaps/show')
                ->where('recap.id', $recap->public_id)
                ->where('recap.outletCode', 'STTSM')
                ->where('recap.productionDate', '2026-10-12')
                ->where('recap.sequence', 2)
                ->where('recap.ready', false)
                ->has('recap.items', 2)
                ->where('recap.items.0.source', 'closing')
                ->where('recap.items.0.key', ClosingTotals::MENU_ID)
                ->where('recap.items.0.code', 'SU-001')
                ->where('recap.items.0.name', 'Sushi Roll Salmon')
                ->where('recap.items.0.totalSold', '13')
                ->where('recap.items.0.waste', '1')
                ->where('recap.items.0.adjustment', '-1')
                ->where('recap.items.0.compensation', '0')
                ->where('recap.items.0.esbMenu', null)
                ->where('recap.items.0.bom', null)
                ->where('recap.items.1.source', 'pos_only')
                ->where('recap.items.1.key', 'DRK-OCHA-01')
                ->has('recap.submissions', 2)
                ->where('recap.submissions.0.sequence', 2)
                ->where('recap.submissions.1.sequence', 1)
                ->missing('recap.submissions.0.body')
                ->where('recap.missing', [
                    ['type' => 'outlet', 'key' => 'STTSM', 'label' => 'STTSM', 'message' => 'Outlet STTSM has no ERP branch and location.'],
                    ['type' => 'item-menu', 'key' => ClosingTotals::MENU_ID, 'label' => 'Sushi Roll Salmon', 'message' => 'Sushi Roll Salmon has no ERP menu for sales.'],
                    ['type' => 'item-bom', 'key' => ClosingTotals::MENU_ID, 'label' => 'Sushi Roll Salmon', 'message' => 'Sushi Roll Salmon has no bill of material for the item journals.'],
                    ['type' => 'item-menu', 'key' => 'DRK-OCHA-01', 'label' => 'Ocha Dingin', 'message' => 'Ocha Dingin has no ERP menu for sales.'],
                    ['type' => 'purpose', 'key' => 'waste', 'label' => 'Waste', 'message' => 'Waste has no ERP purpose.'],
                    ['type' => 'purpose', 'key' => 'adjustment', 'label' => 'Adjustment', 'message' => 'Adjustment has no ERP purpose.'],
                ]));
    }

    #[Test]
    public function the_detail_shows_the_mappings_once_they_exist(): void
    {
        $recap = $this->holdRecap();
        $this->mapEverything();

        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->get(route('closing-recaps.show', $recap))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('recap.ready', true)
                ->where('recap.missing', [])
                ->where('recap.outlet.branchCode', 'STTSM')
                ->where('recap.outlet.locationName', 'Gudang TSM')
                ->where('recap.items.0.esbMenu', 'SU001 · Sushi Roll Salmon')
                ->where('recap.items.0.bom', 'Sushi Roll Salmon')
                ->where('recap.items.1.esbMenu', 'OCHA · Ocha Dingin'));
    }

    #[Test]
    public function a_zero_quantity_needs_no_mapping(): void
    {
        $recap = $this->holdRecap(['items' => [
            ClosingTotals::closingItem(['colorplateSold' => 0, 'posQty' => 0, 'totalSold' => 0, 'waste' => 0, 'adjustment' => 0, 'compensation' => 2]),
        ]]);

        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->get(route('closing-recaps.show', $recap))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('recap.missing.1.type', 'item-bom')
                ->where('recap.missing.2.key', 'compensation')
                ->has('recap.missing', 3));
    }

    #[Test]
    public function product_codes_are_matched_upper_cased(): void
    {
        $recap = $this->holdRecap(['items' => [ClosingTotals::posOnlyItem(['productCode' => 'drk-ocha-01'])]]);
        ClosingItemMapping::query()->create([
            'source' => 'pos_only', 'item_key' => 'DRK-OCHA-01',
            'esb_menu_id' => 190, 'esb_menu_code' => 'OCHA', 'esb_menu_name' => 'Ocha Dingin',
        ]);

        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->get(route('closing-recaps.show', $recap))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('recap.items.0.key', 'DRK-OCHA-01')
                ->where('recap.missing', [
                    ['type' => 'outlet', 'key' => 'STTSM', 'label' => 'STTSM', 'message' => 'Outlet STTSM has no ERP branch and location.'],
                ]));
    }
}
