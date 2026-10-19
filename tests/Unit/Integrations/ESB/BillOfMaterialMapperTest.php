<?php

namespace Tests\Unit\Integrations\ESB;

use App\Integrations\ESB\Mappers\BillOfMaterialMapper;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreBom;
use Tests\TestCase;

/**
 * ESB Core Master Bill of Material onto the canonical BOM ([ESB-014]).
 */
class BillOfMaterialMapperTest extends TestCase
{
    private function mapper(): BillOfMaterialMapper
    {
        return new BillOfMaterialMapper;
    }

    #[Test]
    public function it_maps_a_list_row(): void
    {
        $bom = $this->mapper()->summaryFromRow(CoreBom::row());

        $this->assertSame('esb', $bom->provider);
        $this->assertSame(412, $bom->id);
        $this->assertSame('BOM-NG01', $bom->code);
        $this->assertSame('Nasi Goreng Spesial', $bom->name);
        $this->assertSame(2, $bom->typeId);
        $this->assertSame('Menu', $bom->typeName);
        $this->assertSame('Nasi Goreng Spesial', $bom->productName);
        $this->assertSame('Porsi', $bom->uomName);
        $this->assertNull($bom->notes);
        $this->assertTrue($bom->active);
    }

    #[Test]
    public function a_list_row_reads_defensively(): void
    {
        $bom = $this->mapper()->summaryFromRow([
            'bomID' => 0,
            'bomCode' => '',
            'bomName' => null,
            'flagActive' => 'maybe',
        ]);

        $this->assertNull($bom->id);
        $this->assertNull($bom->code);
        $this->assertSame('', $bom->name);
        $this->assertNull($bom->typeId);
        $this->assertNull($bom->active);
        $this->assertFalse($this->mapper()->summaryFromRow(['flagActive' => 0])->active);
    }

    #[Test]
    public function it_maps_the_detail_header(): void
    {
        $bom = $this->mapper()->fromDetail(CoreBom::detail());

        $this->assertNotNull($bom);
        $this->assertSame('esb', $bom->provider);
        $this->assertSame(412, $bom->id);
        $this->assertSame('BOM-NG01', $bom->code);
        $this->assertSame('Nasi Goreng Spesial', $bom->name);
        $this->assertSame('Menu', $bom->typeName);
        $this->assertSame('NG01', $bom->productCode);
        $this->assertSame('Porsi', $bom->uomName);
        $this->assertSame('Resep standar', $bom->notes);
        $this->assertTrue($bom->active);
        $this->assertSame(18500.5, $bom->materialCostTotal);
        $this->assertSame(1500.0, $bom->otherCostTotal);
        $this->assertSame('admin', $bom->createdBy);
        $this->assertSame('2026-01-10 08:00:00', $bom->createdAt);
        $this->assertNull($bom->editedBy);
    }

    #[Test]
    public function it_maps_material_lines_and_skips_lines_without_a_product(): void
    {
        $lines = $this->mapper()->fromDetail(CoreBom::detail())?->lines ?? [];

        $this->assertCount(2, $lines);
        $this->assertSame(9001, $lines[0]->lineId);
        $this->assertSame(801, $lines[0]->productDetailId);
        $this->assertSame('BRS01', $lines[0]->productCode);
        $this->assertSame('Beras', $lines[0]->productName);
        $this->assertSame('Gram', $lines[0]->uomName);
        $this->assertSame(200.0, $lines[0]->qty);
        $this->assertSame(15.5, $lines[0]->lastCost);
        $this->assertSame(3100.0, $lines[0]->price);
        $this->assertSame(5.0, $lines[0]->tolerancePercent);
        $this->assertSame(100.0, $lines[0]->yieldPercent);
        $this->assertNull($lines[0]->printGroup);
        $this->assertSame(1.5, $lines[1]->qty);
        $this->assertSame('Topping', $lines[1]->printGroup);
    }

    #[Test]
    public function it_maps_substitutes_and_skips_ones_without_a_product(): void
    {
        $lines = $this->mapper()->fromDetail(CoreBom::detail())?->lines ?? [];

        $this->assertCount(1, $lines[0]->substitutes);
        $this->assertSame(802, $lines[0]->substitutes[0]->productDetailId);
        $this->assertSame('BRS02', $lines[0]->substitutes[0]->productCode);
        $this->assertSame('Beras Merah', $lines[0]->substitutes[0]->productName);
        $this->assertSame('Gram', $lines[0]->substitutes[0]->uomName);
        $this->assertSame(1, $lines[0]->substitutes[0]->order);
        $this->assertSame(['Beras Pandan'], $lines[0]->manufacturingSubstitutes);
        $this->assertSame([], $lines[1]->substitutes);
        $this->assertSame([], $lines[1]->manufacturingSubstitutes);
    }

    #[Test]
    public function it_maps_other_costs_and_replacements(): void
    {
        $bom = $this->mapper()->fromDetail(CoreBom::detail());

        $this->assertNotNull($bom);
        $this->assertCount(1, $bom->costs);
        $this->assertSame('5101', $bom->costs[0]->accountNo);
        $this->assertSame('Gas', $bom->costs[0]->description);
        $this->assertSame(1500.0, $bom->costs[0]->total);

        $this->assertCount(1, $bom->replacements);
        $this->assertSame('NG01', $bom->replacements[0]->menuCode);
        $this->assertSame('TLR01', $bom->replacements[0]->productCode);
        $this->assertSame('TLB01', $bom->replacements[0]->replacementProductCode);
        $this->assertSame('Telur Bebek', $bom->replacements[0]->replacementProductName);
        $this->assertSame(1.0, $bom->replacements[0]->qty);
    }

    #[Test]
    public function a_detail_without_an_id_is_no_bom(): void
    {
        $this->assertNull($this->mapper()->fromDetail([]));
        $this->assertNull($this->mapper()->fromDetail(CoreBom::detail(['bomID' => 0])));
    }

    #[Test]
    public function malformed_nested_lists_become_empty(): void
    {
        $bom = $this->mapper()->fromDetail(CoreBom::detail([
            'bomDetails' => 'nope',
            'bomCosts' => [null, 'x'],
            'bomReplacements' => null,
        ]));

        $this->assertNotNull($bom);
        $this->assertSame([], $bom->lines);
        $this->assertSame([], $bom->costs);
        $this->assertSame([], $bom->replacements);
    }
}
