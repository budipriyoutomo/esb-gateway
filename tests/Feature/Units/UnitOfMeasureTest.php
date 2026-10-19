<?php

namespace Tests\Feature\Units;

use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\MasterData\ReadOnlyMasterDataTestCase;
use Tests\Fixtures\CoreUnitOfMeasure;

/**
 * ESB Core units, read-only ([ESB-020]).
 */
class UnitOfMeasureTest extends ReadOnlyMasterDataTestCase
{
    protected function resource(): string
    {
        return 'units';
    }

    protected function listPath(): string
    {
        return '/units';
    }

    protected function detailPath(): string
    {
        return '/units';
    }

    protected function sampleId(): int
    {
        return 4;
    }

    protected function idField(): string
    {
        return 'uomID';
    }

    protected function operation(): string
    {
        return 'unit';
    }

    protected function listProp(): string
    {
        return 'units';
    }

    protected function detailProp(): string
    {
        return 'unit';
    }

    protected function listResponse(int $count = 1, int $page = 1): array
    {
        return CoreUnitOfMeasure::listResponse($count, $page);
    }

    protected function detailResponse(array $overrides = []): array
    {
        return CoreUnitOfMeasure::detailResponse($overrides);
    }

    protected function filterCase(): array
    {
        return [
            ['search' => 'kg', 'flagActive' => 0],
            ['search' => 'kg', 'flagActive' => 0],
            'indexSearch=kg&flagActive=0',
        ];
    }

    #[Test]
    public function list_rows_carry_the_unit_fields(): void
    {
        Http::fake(['*' => Http::response(CoreUnitOfMeasure::listResponse())]);

        $this->actingAs($this->operator())
            ->get(route('units.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('units.0.id', 4)
                ->where('units.0.name', 'KG')
                ->where('units.0.metricName', 'Weight')
                ->where('units.0.taxCode', 'KGM')
                ->where('units.0.active', true));
    }

    #[Test]
    public function the_detail_carries_who_created_and_edited_it(): void
    {
        Http::fake(['*' => Http::response(CoreUnitOfMeasure::detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('units.show', 4))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('unit.name', 'KG')
                ->where('unit.notes', 'Kilogram')
                ->where('unit.createdBy', 'admin')
                ->where('unit.editedBy', null));
    }
}
