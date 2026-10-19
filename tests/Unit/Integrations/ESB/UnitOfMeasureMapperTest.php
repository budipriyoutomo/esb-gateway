<?php

namespace Tests\Unit\Integrations\ESB;

use App\Integrations\ESB\Mappers\UnitOfMeasureMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\CoreUnitOfMeasure;

class UnitOfMeasureMapperTest extends TestCase
{
    #[Test]
    public function a_row_maps_onto_a_summary(): void
    {
        $unit = (new UnitOfMeasureMapper)->summaryFromRow(CoreUnitOfMeasure::row());

        $this->assertSame('esb', $unit->provider);
        $this->assertSame(4, $unit->id);
        $this->assertSame('KG', $unit->name);
        $this->assertSame(2, $unit->metricId);
        $this->assertSame('Weight', $unit->metricName);
        $this->assertSame('KGM', $unit->taxCode);
        $this->assertTrue($unit->active);
    }

    #[Test]
    public function zero_and_empty_values_mean_none(): void
    {
        $unit = (new UnitOfMeasureMapper)->summaryFromRow(CoreUnitOfMeasure::row([
            'uomID' => 0,
            'metricID' => 0,
            'metricName' => '',
            'refTaxCode' => '',
            'flagActive' => 'yes',
        ]));

        $this->assertNull($unit->id);
        $this->assertNull($unit->metricId);
        $this->assertNull($unit->metricName);
        $this->assertNull($unit->taxCode);
        // Not 1/0: unknown, not false.
        $this->assertNull($unit->active);
    }

    #[Test]
    public function the_detail_maps_with_its_audit_fields(): void
    {
        $unit = (new UnitOfMeasureMapper)->fromDetail(CoreUnitOfMeasure::detailResponse()['result']);

        $this->assertNotNull($unit);
        $this->assertSame(9, $unit->taxId);
        $this->assertSame('admin', $unit->createdBy);
        $this->assertSame('2026-01-10 08:00:00', $unit->createdAt);
        $this->assertNull($unit->editedBy);
    }

    #[Test]
    public function a_detail_without_an_id_is_no_unit(): void
    {
        $this->assertNull((new UnitOfMeasureMapper)->fromDetail(['uomID' => 0, 'uomName' => '']));
        $this->assertNull((new UnitOfMeasureMapper)->fromDetail([]));
    }
}
