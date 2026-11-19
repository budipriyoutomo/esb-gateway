<?php

namespace Tests\Feature\VisitPurposes;

use App\Audit\AuditAction;
use App\Authorization\Role;
use App\Models\AuditLog;
use App\Models\VisitPurpose;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ClosingRecaps\HoldsClosingRecaps;
use Tests\Fixtures\ClosingTotals;
use Tests\TestCase;

/**
 * Visit purposes, a master kept in the gateway: the official list of the
 * visitPurposeName values closing recaps carry (ADR-032). Not read from the
 * ESB: its only list (OMS Master_POS get-visit-purpose) needs a POS backend
 * credential the gateway does not hold ([ESB-052]).
 */
class VisitPurposeTest extends TestCase
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
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function body(array $overrides = []): array
    {
        return ['name' => 'Dine In', 'notes' => 'Eaten at the outlet', 'active' => true, ...$overrides];
    }

    private function purpose(string $name, bool $active = true): VisitPurpose
    {
        return VisitPurpose::query()->create([
            'name' => $name,
            'name_key' => VisitPurpose::keyOf($name),
            'is_active' => $active,
        ]);
    }

    // =========================================================== access

    #[Test]
    public function guests_and_viewers_cannot_reach_visit_purposes(): void
    {
        $this->get(route('visit-purposes.index'))->assertRedirect(route('login'));

        $viewer = $this->userWithRole(Role::Viewer);
        $this->actingAs($viewer)->get(route('visit-purposes.index'))->assertForbidden();
        $this->actingAs($viewer)->post(route('visit-purposes.store'), $this->body())->assertForbidden();
    }

    #[Test]
    public function seeing_is_not_changing(): void
    {
        $itAdmin = $this->userWithRole(Role::ItAdmin);
        $purpose = $this->purpose('DINE IN');

        $this->actingAs($itAdmin)
            ->get(route('visit-purposes.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('canManage', false));

        $this->actingAs($itAdmin)->post(route('visit-purposes.store'), $this->body(['name' => 'Delivery']))->assertForbidden();
        $this->actingAs($itAdmin)->put(route('visit-purposes.update', $purpose), $this->body())->assertForbidden();
        $this->actingAs($itAdmin)->delete(route('visit-purposes.destroy', $purpose))->assertForbidden();

        $this->assertSame(1, VisitPurpose::query()->count());
        $this->assertSame(0, AuditLog::query()->count());
    }

    // ============================================================= list

    #[Test]
    public function the_list_shows_registered_purposes_and_names_seen_in_recaps(): void
    {
        $this->holdRecap(ClosingTotals::twoPurposes());
        $this->holdRecap(ClosingTotals::revision3(['productionDate' => '2026-10-13']));
        $this->purpose('dine in');
        $this->purpose('Delivery', active: false);

        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->get(route('visit-purposes.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('visit-purposes/index')
                ->where('canManage', true)
                ->has('visitPurposes', 2)
                ->where('visitPurposes.0.name', 'Delivery')
                ->where('visitPurposes.0.active', false)
                ->where('visitPurposes.0.recaps', 0)
                // Matched however the POS spells its case.
                ->where('visitPurposes.1.name', 'dine in')
                ->where('visitPurposes.1.recaps', 2)
                ->has('unregistered', 1)
                ->where('unregistered.0.name', 'TAKE AWAY')
                ->where('unregistered.0.recaps', 1));

        Http::assertNothingSent();
    }

    // ============================================================ write

    #[Test]
    public function an_operator_can_add_a_visit_purpose(): void
    {
        $operator = $this->userWithRole(Role::IntegrationOperator);

        $this->actingAs($operator)
            ->post(route('visit-purposes.store'), $this->body(['name' => '  Dine In  ']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $purpose = VisitPurpose::query()->sole();
        $this->assertSame('Dine In', $purpose->name);
        $this->assertSame('DINE IN', $purpose->name_key);
        $this->assertSame('Eaten at the outlet', $purpose->notes);
        $this->assertTrue($purpose->is_active);
        $this->assertSame($operator->id, $purpose->updated_by);

        $audit = AuditLog::query()->sole();
        $this->assertSame(AuditAction::VisitPurposeSaved->value, $audit->action);
        $this->assertNull($audit->metadata['before']);
        $this->assertSame('Dine In', $audit->metadata['after']['name']);
    }

    #[Test]
    public function an_operator_can_edit_a_visit_purpose(): void
    {
        $purpose = $this->purpose('Dine In');

        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->put(route('visit-purposes.update', $purpose), $this->body(['name' => 'DINE-IN', 'notes' => '', 'active' => false]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $purpose->refresh();
        $this->assertSame('DINE-IN', $purpose->name);
        $this->assertNull($purpose->notes);
        $this->assertFalse($purpose->is_active);

        $audit = AuditLog::query()->sole();
        $this->assertSame('Dine In', $audit->metadata['before']['name']);
        $this->assertFalse($audit->metadata['after']['active']);
    }

    #[Test]
    public function a_name_is_unique_whatever_its_case(): void
    {
        $existing = $this->purpose('Dine In');
        $other = $this->purpose('Delivery');
        $operator = $this->userWithRole(Role::IntegrationOperator);

        $this->actingAs($operator)
            ->post(route('visit-purposes.store'), $this->body(['name' => ' dine in ']))
            ->assertSessionHasErrors('name');

        $this->actingAs($operator)
            ->put(route('visit-purposes.update', $other), $this->body(['name' => 'DINE IN']))
            ->assertSessionHasErrors('name');

        // Keeping its own name is not a clash.
        $this->actingAs($operator)
            ->put(route('visit-purposes.update', $existing), $this->body(['name' => 'DINE IN']))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, VisitPurpose::query()->count());
    }

    #[Test]
    public function bad_input_is_refused(): void
    {
        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->post(route('visit-purposes.store'), [
                'name' => str_repeat('x', 101),
                'notes' => str_repeat('x', 256),
                'active' => 'maybe',
            ])
            ->assertSessionHasErrors(['name', 'notes', 'active']);

        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->post(route('visit-purposes.store'), ['name' => '   ', 'active' => true])
            ->assertSessionHasErrors(['name']);

        $this->assertSame(0, VisitPurpose::query()->count());
    }

    #[Test]
    public function an_operator_can_remove_a_visit_purpose(): void
    {
        $purpose = $this->purpose('Dine In');

        $this->actingAs($this->userWithRole(Role::IntegrationOperator))
            ->delete(route('visit-purposes.destroy', $purpose))
            ->assertRedirect();

        $this->assertSame(0, VisitPurpose::query()->count());

        $audit = AuditLog::query()->sole();
        $this->assertSame(AuditAction::VisitPurposeDeleted->value, $audit->action);
        $this->assertSame('Dine In', $audit->metadata['before']['name']);
    }

    #[Test]
    public function an_unknown_visit_purpose_is_a_404(): void
    {
        $operator = $this->userWithRole(Role::IntegrationOperator);

        $this->actingAs($operator)->put('/visit-purposes/999', $this->body())->assertNotFound();
        $this->actingAs($operator)->delete('/visit-purposes/999')->assertNotFound();
        $this->actingAs($operator)->delete('/visit-purposes/abc')->assertNotFound();
    }
}
