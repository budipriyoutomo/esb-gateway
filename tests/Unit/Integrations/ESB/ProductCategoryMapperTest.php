<?php

namespace Tests\Unit\Integrations\ESB;

use App\Integrations\ESB\Mappers\ProductCategoryMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\CoreProductCategory;

class ProductCategoryMapperTest extends TestCase
{
    #[Test]
    public function a_row_maps_onto_a_summary(): void
    {
        $category = (new ProductCategoryMapper)->summaryFromRow(CoreProductCategory::row());

        $this->assertSame(3, $category->id);
        $this->assertSame('Bahan Baku', $category->name);
        $this->assertSame(1, $category->typeId);
        $this->assertSame('Inventory', $category->typeName);
        $this->assertNull($category->notes);
        $this->assertTrue($category->active);
    }

    #[Test]
    public function the_detail_maps_its_accounts_and_drops_empty_ones(): void
    {
        $category = (new ProductCategoryMapper)->fromDetail(CoreProductCategory::detailResponse()['result']);

        $this->assertNotNull($category);
        $this->assertSame('BB', $category->code);
        $this->assertSame('1301', $category->coaNo);
        $this->assertSame('5101', $category->cogsCoaNo);
        $this->assertSame('5901', $category->opnameCoaNo);
        $this->assertNull($category->accrualBasisCoaNo);
        $this->assertNull($category->productSalesCoaNo);
        $this->assertNull($category->depreciationCoaNo);
        $this->assertNull($category->depreciationExpenseCoaNo);
        // 0 months / type 0 means "not depreciated", not a zero-month schedule.
        $this->assertNull($category->depreciationMonths);
        $this->assertNull($category->depreciationTypeId);
        $this->assertFalse($category->assetTax);
    }

    #[Test]
    public function the_detail_names_its_type_from_the_vendor_legend(): void
    {
        $mapper = new ProductCategoryMapper;

        $names = array_map(
            fn (int $type) => $mapper->fromDetail(CoreProductCategory::detailResponse(['categoryTypeID' => $type])['result'])?->typeName,
            [1, 2, 3, 4, 9],
        );

        $this->assertSame(['Inventory', 'Non Inventory', 'Asset', 'Non Depreciated Asset', null], $names);
    }

    #[Test]
    public function an_asset_category_keeps_its_depreciation_schedule(): void
    {
        $category = (new ProductCategoryMapper)->fromDetail(CoreProductCategory::detailResponse([
            'categoryTypeID' => 3,
            'depCoaNo' => '1801',
            'depExpenseCoaNo' => '6801',
            'depLength' => 48,
            'depLengthFiscal' => 60,
            'depreciationTypeID' => 1,
            'flagAssetTax' => 1,
        ])['result']);

        $this->assertNotNull($category);
        $this->assertSame('1801', $category->depreciationCoaNo);
        $this->assertSame(48, $category->depreciationMonths);
        $this->assertSame(60, $category->fiscalDepreciationMonths);
        $this->assertSame(1, $category->depreciationTypeId);
        $this->assertTrue($category->assetTax);
    }

    #[Test]
    public function a_detail_without_an_id_is_no_category(): void
    {
        $this->assertNull((new ProductCategoryMapper)->fromDetail(['categoryID' => 0]));
    }
}
