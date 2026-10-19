<?php

namespace Tests\Unit\Integrations\ESB;

use App\Integrations\ESB\Mappers\ProductSubCategoryMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\CoreProductSubCategory;

class ProductSubCategoryMapperTest extends TestCase
{
    #[Test]
    public function a_row_maps_onto_a_summary(): void
    {
        $subCategory = (new ProductSubCategoryMapper)->summaryFromRow(CoreProductSubCategory::row());

        $this->assertSame(7, $subCategory->id);
        $this->assertSame('Beras', $subCategory->name);
        $this->assertSame(90, $subCategory->deadStockDays);
        $this->assertNull($subCategory->notes);
        $this->assertTrue($subCategory->active);
    }

    #[Test]
    public function a_zero_threshold_is_none(): void
    {
        $subCategory = (new ProductSubCategoryMapper)->summaryFromRow(CoreProductSubCategory::row([
            'deadStockThreshold' => 0,
            'flagActive' => 0,
        ]));

        $this->assertNull($subCategory->deadStockDays);
        $this->assertFalse($subCategory->active);
    }

    #[Test]
    public function the_detail_maps_with_its_audit_fields(): void
    {
        $subCategory = (new ProductSubCategoryMapper)->fromDetail(CoreProductSubCategory::detailResponse()['result']);

        $this->assertNotNull($subCategory);
        $this->assertSame('Semua jenis beras', $subCategory->notes);
        $this->assertSame('admin', $subCategory->createdBy);
        $this->assertNull($subCategory->editedAt);
    }

    #[Test]
    public function a_detail_without_an_id_is_no_sub_category(): void
    {
        $this->assertNull((new ProductSubCategoryMapper)->fromDetail(['subCategoryID' => 0]));
    }
}
