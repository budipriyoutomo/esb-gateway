<?php

namespace Tests\Unit\Integrations\ESB;

use App\Integrations\ESB\Mappers\SupplierCategoryMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\CoreSupplierCategory;

class SupplierCategoryMapperTest extends TestCase
{
    #[Test]
    public function a_row_maps_onto_a_summary(): void
    {
        $category = (new SupplierCategoryMapper)->summaryFromRow(CoreSupplierCategory::row());

        $this->assertSame('esb', $category->provider);
        $this->assertSame(5, $category->id);
        $this->assertSame('Bahan Pokok', $category->name);
        $this->assertTrue($category->active);
    }

    #[Test]
    public function a_missing_name_is_an_empty_name_not_a_crash(): void
    {
        $category = (new SupplierCategoryMapper)->summaryFromRow(['supplierCategoryID' => 5, 'supplierCategoryName' => null]);

        $this->assertSame('', $category->name);
        $this->assertNull($category->active);
    }

    #[Test]
    public function the_detail_maps_with_its_audit_fields(): void
    {
        $category = (new SupplierCategoryMapper)->fromDetail(CoreSupplierCategory::detailResponse()['result']);

        $this->assertNotNull($category);
        $this->assertSame(5, $category->id);
        $this->assertSame('admin', $category->createdBy);
    }

    #[Test]
    public function a_detail_without_an_id_is_no_category(): void
    {
        $this->assertNull((new SupplierCategoryMapper)->fromDetail(['supplierCategoryID' => 0]));
    }
}
