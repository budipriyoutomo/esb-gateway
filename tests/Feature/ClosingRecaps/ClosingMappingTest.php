<?php

namespace Tests\Feature\ClosingRecaps;

use App\Audit\AuditAction;
use App\Authorization\Role;
use App\Models\AuditLog;
use App\Models\ClosingItemMapping;
use App\Models\ClosingOutletMapping;
use App\Models\ClosingPurposeMapping;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\ClosingTotals;
use Tests\TestCase;

/**
 * How closing recaps meet the ERP (ADR-032): outlet -> branch and location,
 * menu or POS product -> ERP menu and bill of material, and each stock kind
 * -> purpose and direction. Kept by an operator in the gateway.
 */
class ClosingMappingTest extends TestCase
{
    use HoldsClosingRecaps;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function outletBody(array $overrides = []): array
    {
        return [
            'branchId' => 12,
            'branchCode' => 'STTSM',
            'branchName' => 'Sushi Tei TSM',
            'locationId' => 31,
            'locationName' => 'Gudang TSM',
            ...$overrides,
        ];
    }

    // =========================================================== access

    #[Test]
    public function guests_and_viewers_cannot_reach_the_mappings(): void
    {
        $this->get(route('closing-mappings.outlets'))->assertRedirect(route('login'));

        $viewer = $this->userWithRole(Role::Viewer);
        $this->actingAs($viewer)->get(route('closing-mappings.outlets'))->assertForbidden();
        $this->actingAs($viewer)->get(route('closing-mappings.items'))->assertForbidden();
        $this->actingAs($viewer)->get(route('closing-mappings.purposes'))->assertForbidden();
    }

    #[Test]
    public function seeing_the_mappings_is_not_enough_to_change_them(): void
    {
        $itAdmin = $this->userWithRole(Role::ItAdmin);

        $this->actingAs($itAdmin)->get(route('closing-mappings.outlets'))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('canManage', false));

        $this->actingAs($itAdmin)
            ->put(route('closing-mappings.outlets.update', 'STTSM'), $this->outletBody())
            ->assertForbidden();
        $this->actingAs($itAdmin)
            ->put(route('closing-mappings.purposes.update', 'waste'), ['purposeId' => 7, 'purposeName' => 'Waste', 'direction' => -1])
            ->assertForbidden();

        $this->assertSame(0, ClosingOutletMapping::query()->count());
    }

    // ========================================================== outlets

    #[Test]
    public function outlets_seen_in_recaps_are_listed_with_their_mapping(): void
    {
        $this->holdRecap();
        $this->holdRecap(['outlet' => ['code' => 'RXT']]);
        ClosingOutletMapping::query()->create([
            'outlet_code' => 'RXT', 'branch_id' => 3, 'branch_code' => 'RXT', 'branch_name' => 'Roxy',
            'location_id' => 9, 'location_name' => 'Dapur Roxy',
        ]);

        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->get(route('closing-mappings.outlets'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('closing-mappings/outlets')
                ->where('canManage', true)
                ->has('outlets', 2)
                ->where('outlets.0.outletCode', 'RXT')
                ->where('outlets.0.mapping.branchName', 'Roxy')
                ->where('outlets.0.mapping.locationName', 'Dapur Roxy')
                ->where('outlets.0.recaps', 1)
                ->where('outlets.1.outletCode', 'STTSM')
                ->where('outlets.1.mapping', null));
    }

    #[Test]
    public function an_outlet_is_mapped_and_remapped_with_an_audit_row_each_time(): void
    {
        $operator = $this->userWithRole(Role::IntegrationOperator);

        $this->actingAs($operator)
            ->put(route('closing-mappings.outlets.update', 'sttsm'), $this->outletBody())
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $mapping = ClosingOutletMapping::query()->sole();
        $this->assertSame('STTSM', $mapping->outlet_code);
        $this->assertSame(12, $mapping->branch_id);
        $this->assertSame(31, $mapping->location_id);
        $this->assertSame($operator->id, $mapping->updated_by);

        $this->actingAs($operator)
            ->put(route('closing-mappings.outlets.update', 'STTSM'), $this->outletBody(['locationId' => 32, 'locationName' => 'Dapur TSM']))
            ->assertSessionHasNoErrors();

        $this->assertSame(32, ClosingOutletMapping::query()->sole()->location_id);

        $audits = AuditLog::query()->where('action', AuditAction::ClosingMappingSaved->value)->orderBy('id')->get();
        $this->assertCount(2, $audits);
        $this->assertSame('outlet', $audits[1]->metadata['type']);
        $this->assertSame('STTSM', $audits[1]->metadata['key']);
        $this->assertSame(31, $audits[1]->metadata['before']['locationId']);
        $this->assertSame(32, $audits[1]->metadata['after']['locationId']);
    }

    #[Test]
    public function an_outlet_mapping_needs_a_branch_and_location_and_a_short_branch_code(): void
    {
        $operator = $this->userWithRole(Role::IntegrationOperator);

        $this->actingAs($operator)
            ->put(route('closing-mappings.outlets.update', 'STTSM'), [])
            ->assertSessionHasErrors(['branchId', 'branchCode', 'branchName', 'locationId', 'locationName']);

        // salesNum is CL + branch code + YYYYMMDD, at most 20 characters.
        $this->actingAs($operator)
            ->put(route('closing-mappings.outlets.update', 'STTSM'), $this->outletBody(['branchCode' => 'ABCDEFGHIJK']))
            ->assertSessionHasErrors(['branchCode']);

        $this->assertSame(0, ClosingOutletMapping::query()->count());
    }

    #[Test]
    public function an_outlet_mapping_is_removed_with_an_audit_row(): void
    {
        $operator = $this->userWithRole(Role::IntegrationOperator);
        $this->actingAs($operator)->put(route('closing-mappings.outlets.update', 'STTSM'), $this->outletBody());

        $this->actingAs($operator)
            ->delete(route('closing-mappings.outlets.destroy', 'STTSM'))
            ->assertRedirect();

        $this->assertSame(0, ClosingOutletMapping::query()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::ClosingMappingDeleted->value]);

        $this->actingAs($operator)->delete(route('closing-mappings.outlets.destroy', 'STTSM'))->assertNotFound();
    }

    // ============================================================ items

    #[Test]
    public function items_seen_in_recaps_are_listed_with_their_mapping(): void
    {
        $this->holdRecap();
        ClosingItemMapping::query()->create([
            'source' => 'pos_only', 'item_key' => 'DRK-OCHA-01',
            'esb_menu_id' => 190, 'esb_menu_code' => 'OCHA', 'esb_menu_name' => 'Ocha Dingin',
        ]);

        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->get(route('closing-mappings.items'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('closing-mappings/items')
                ->has('items.data', 2)
                ->where('items.data.0.source', 'closing')
                ->where('items.data.0.key', ClosingTotals::MENU_ID)
                ->where('items.data.0.code', 'SU-001')
                ->where('items.data.0.name', 'Sushi Roll Salmon')
                ->where('items.data.0.needsBom', true)
                ->where('items.data.0.mapping', null)
                ->where('items.data.1.source', 'pos_only')
                ->where('items.data.1.key', 'DRK-OCHA-01')
                ->where('items.data.1.needsBom', false)
                ->where('items.data.1.mapping.esbMenuCode', 'OCHA'));
    }

    #[Test]
    public function items_are_filtered_by_source_search_and_unmapped(): void
    {
        $this->holdRecap();
        ClosingItemMapping::query()->create([
            'source' => 'pos_only', 'item_key' => 'DRK-OCHA-01',
            'esb_menu_id' => 190, 'esb_menu_code' => 'OCHA', 'esb_menu_name' => 'Ocha Dingin',
        ]);
        $operator = $this->userWithRole(Role::IntegrationOperator);

        $this->actingAs($operator)->get(route('closing-mappings.items', ['source' => 'pos_only']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('items.data', 1)->where('items.data.0.source', 'pos_only'));

        $this->actingAs($operator)->get(route('closing-mappings.items', ['search' => 'salmon']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('items.data', 1)->where('items.data.0.code', 'SU-001'));

        $this->actingAs($operator)->get(route('closing-mappings.items', ['unmapped' => '1']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('items.data', 1)->where('items.data.0.source', 'closing'));
    }

    #[Test]
    public function a_menu_is_mapped_to_an_erp_menu_and_a_bill_of_material(): void
    {
        $operator = $this->userWithRole(Role::IntegrationOperator);

        $this->actingAs($operator)
            ->put(route('closing-mappings.items.update', ['closing', strtoupper(ClosingTotals::MENU_ID)]), [
                'esbMenuId' => 188, 'esbMenuCode' => 'SU001', 'esbMenuName' => 'Sushi Roll Salmon',
                'bomId' => 412, 'bomName' => 'Sushi Roll Salmon',
            ])
            ->assertSessionHasNoErrors();

        $mapping = ClosingItemMapping::query()->sole();
        $this->assertSame(ClosingTotals::MENU_ID, $mapping->item_key);
        $this->assertSame(188, $mapping->esb_menu_id);
        $this->assertSame(412, $mapping->bom_id);
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::ClosingMappingSaved->value]);
    }

    #[Test]
    public function a_pos_product_is_mapped_to_a_menu_without_a_bill_of_material(): void
    {
        $operator = $this->userWithRole(Role::IntegrationOperator);

        $this->actingAs($operator)
            ->put(route('closing-mappings.items.update', ['pos_only', 'drk-ocha-01']), [
                'esbMenuId' => 190, 'esbMenuCode' => 'OCHA', 'esbMenuName' => 'Ocha Dingin',
                'bomId' => 412, 'bomName' => 'Ocha',
            ])
            ->assertSessionHasErrors(['bomId']);

        $this->actingAs($operator)
            ->put(route('closing-mappings.items.update', ['pos_only', 'drk-ocha-01']), [
                'esbMenuId' => 190, 'esbMenuCode' => 'OCHA', 'esbMenuName' => 'Ocha Dingin',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('DRK-OCHA-01', ClosingItemMapping::query()->sole()->item_key);
    }

    #[Test]
    public function an_item_mapping_names_a_menu_or_a_bill_of_material(): void
    {
        $operator = $this->userWithRole(Role::IntegrationOperator);

        $this->actingAs($operator)
            ->put(route('closing-mappings.items.update', ['closing', ClosingTotals::MENU_ID]), [])
            ->assertSessionHasErrors(['esbMenuId']);

        $this->actingAs($operator)
            ->put(route('closing-mappings.items.update', ['closing', ClosingTotals::MENU_ID]), ['esbMenuId' => 188])
            ->assertSessionHasErrors(['esbMenuCode', 'esbMenuName']);

        // A BOM alone is enough for a menu that is only wasted, never sold.
        $this->actingAs($operator)
            ->put(route('closing-mappings.items.update', ['closing', ClosingTotals::MENU_ID]), ['bomId' => 412, 'bomName' => 'Sushi Roll Salmon'])
            ->assertSessionHasNoErrors();
    }

    #[Test]
    public function an_item_key_must_fit_its_source(): void
    {
        $operator = $this->userWithRole(Role::IntegrationOperator);

        $this->actingAs($operator)
            ->put(route('closing-mappings.items.update', ['closing', 'not-a-uuid']), ['bomId' => 1, 'bomName' => 'x'])
            ->assertNotFound();

        $this->actingAs($operator)
            ->put(route('closing-mappings.items.update', ['manual', 'X']), ['bomId' => 1, 'bomName' => 'x'])
            ->assertNotFound();
    }

    #[Test]
    public function an_item_mapping_is_removed(): void
    {
        $operator = $this->userWithRole(Role::IntegrationOperator);
        $this->actingAs($operator)->put(route('closing-mappings.items.update', ['pos_only', 'DRK-OCHA-01']), [
            'esbMenuId' => 190, 'esbMenuCode' => 'OCHA', 'esbMenuName' => 'Ocha Dingin',
        ]);

        $this->actingAs($operator)
            ->delete(route('closing-mappings.items.destroy', ['pos_only', 'drk-ocha-01']))
            ->assertRedirect();

        $this->assertSame(0, ClosingItemMapping::query()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::ClosingMappingDeleted->value]);
    }

    // ========================================================= purposes

    #[Test]
    public function the_three_kinds_are_always_listed(): void
    {
        ClosingPurposeMapping::query()->create(['kind' => 'waste', 'purpose_id' => 7, 'purpose_name' => 'Waste', 'direction' => -1]);

        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->get(route('closing-mappings.purposes'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('closing-mappings/purposes')
                ->has('purposes', 3)
                ->where('purposes.0.kind', 'waste')
                ->where('purposes.0.mapping.purposeName', 'Waste')
                ->where('purposes.0.mapping.direction', -1)
                ->where('purposes.1.kind', 'adjustment')
                ->where('purposes.1.mapping', null)
                ->where('purposes.2.kind', 'compensation'));
    }

    #[Test]
    public function a_kind_is_mapped_to_a_purpose_and_a_direction(): void
    {
        $operator = $this->userWithRole(Role::IntegrationOperator);

        $this->actingAs($operator)
            ->put(route('closing-mappings.purposes.update', 'compensation'), ['purposeId' => 9, 'purposeName' => 'Kompensasi', 'direction' => -1])
            ->assertSessionHasNoErrors();

        $mapping = ClosingPurposeMapping::query()->sole();
        $this->assertSame(9, $mapping->purpose_id);
        $this->assertSame(-1, $mapping->direction);

        $this->actingAs($operator)
            ->put(route('closing-mappings.purposes.update', 'compensation'), ['purposeId' => 9, 'purposeName' => 'Kompensasi', 'direction' => 2])
            ->assertSessionHasErrors(['direction']);

        $this->actingAs($operator)
            ->put(route('closing-mappings.purposes.update', 'giveaway'), ['purposeId' => 9, 'purposeName' => 'x', 'direction' => 1])
            ->assertNotFound();
    }
}
