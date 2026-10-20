<?php

namespace Tests\Feature\Units;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Feature\MasterData\MasterDataFormTestCase;
use Tests\Fixtures\CoreUnitOfMeasure;

/**
 * Creating and updating units ([ESB-030], S3).
 */
class UnitOfMeasureFormTest extends MasterDataFormTestCase
{
    protected function resource(): string
    {
        return 'units';
    }

    protected function prefix(): string
    {
        return 'unit';
    }

    protected function pageComponent(): string
    {
        return 'units/form';
    }

    protected function recordProp(): string
    {
        return 'unit';
    }

    protected function sampleId(): int
    {
        return 4;
    }

    protected function createPath(): string
    {
        return '/units';
    }

    protected function updatePath(): string
    {
        return '/units/4';
    }

    protected function detailResponse(): array
    {
        return CoreUnitOfMeasure::detailResponse();
    }

    protected function validInput(): array
    {
        return ['name' => 'KG', 'metricId' => 2, 'taxId' => 9, 'notes' => 'Kilogram'];
    }

    protected function expectedBody(): array
    {
        return ['uomName' => 'KG', 'metricID' => 2, 'notes' => 'Kilogram', 'refTaxID' => 9];
    }

    protected function invalidInputs(): array
    {
        return [
            [['name' => ''], 'name'],
            [['name' => str_repeat('a', 51)], 'name'],
            [['metricId' => null], 'metricId'],
            [['metricId' => 'abc'], 'metricId'],
            [['taxId' => 0], 'taxId'],
            [['notes' => str_repeat('a', 101)], 'notes'],
        ];
    }

    protected function vendorFieldError(): array
    {
        return ['uomName', 'name'];
    }

    /**
     * Without a tax the reference is left out rather than sent as 0, which
     * the ERP may check against its taxes.
     */
    public function test_a_unit_without_a_tax_sends_no_tax_reference(): void
    {
        $sent = null;
        Http::fake(function (Request $request) use (&$sent) {
            $sent = $request->data();

            return Http::response($this->envelope('/units', []));
        });

        $this->actingAs($this->manager())
            ->post(route('units.store'), ['name' => 'KG', 'metricId' => 2, 'taxId' => null, 'notes' => null])
            ->assertSessionHasNoErrors();

        $this->assertSame(['uomName' => 'KG', 'metricID' => 2, 'notes' => ''], $sent);
    }
}
