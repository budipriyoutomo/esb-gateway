<?php

namespace Tests\Unit\Integrations\ESB;

use App\Integrations\ESB\Mappers\PricelistMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\CorePricelist;

class PricelistMapperTest extends TestCase
{
    #[Test]
    public function an_active_row_maps_onto_an_entry(): void
    {
        $entry = (new PricelistMapper)->entryFromRow(CorePricelist::row());

        $this->assertSame(812, $entry->rowId);
        $this->assertSame('PL202603010004', $entry->pricelistNumber);
        $this->assertSame('Beras Premium', $entry->productName);
        $this->assertSame(501, $entry->productDetailId);
        $this->assertSame('KG', $entry->unit);
        $this->assertSame('PT Sumber Pangan', $entry->supplierName);
        $this->assertSame('IDR', $entry->currencyName);
        $this->assertSame(14500.0, $entry->price);
        $this->assertSame('2026-03-01', $entry->priceDate);
        $this->assertNull($entry->expireDate);
        $this->assertTrue($entry->branches->all);
        $this->assertSame([], $entry->branches->names);
    }

    #[Test]
    public function specific_branches_are_named_and_an_unknown_scope_is_null(): void
    {
        $mapper = new PricelistMapper;

        $specific = $mapper->tempSummaryFromRow(CorePricelist::tempRow());
        $odd = $mapper->entryFromRow(CorePricelist::row(['applicableBranch' => ['type' => 'SOME']]));
        $missing = $mapper->entryFromRow(CorePricelist::row(['applicableBranch' => null]));

        $this->assertFalse($specific->branches->all);
        $this->assertSame(['Jakarta Pusat'], $specific->branches->names);
        $this->assertNull($odd->branches->all);
        $this->assertNull($missing->branches->all);
    }

    #[Test]
    public function a_temporary_row_carries_its_status(): void
    {
        $summary = (new PricelistMapper)->tempSummaryFromRow(CorePricelist::tempRow());

        $this->assertSame(CorePricelist::TEMP_NUMBER, $summary->number);
        $this->assertSame(38, $summary->statusId);
        $this->assertSame('Waiting For Approval', $summary->statusName);
        $this->assertSame('2026-06-30', $summary->expireDate);
    }

    #[Test]
    public function the_temporary_detail_carries_its_lines_and_audit(): void
    {
        $pricelist = (new PricelistMapper)->tempFromDetail(CorePricelist::tempDetailResponse()['result']);

        $this->assertNotNull($pricelist);
        $this->assertSame(10, $pricelist->supplierId);
        $this->assertCount(1, $pricelist->lines);
        $this->assertSame(15000.0, $pricelist->lines[0]->price);
        $this->assertSame(14500.0, $pricelist->lines[0]->lastPrice);
        $this->assertSame('budi', $pricelist->createdBy);
        $this->assertNull($pricelist->rejectedBy);
    }

    #[Test]
    public function a_temporary_detail_without_a_number_is_none(): void
    {
        $this->assertNull((new PricelistMapper)->tempFromDetail(['pricelistNum' => '']));
    }
}
