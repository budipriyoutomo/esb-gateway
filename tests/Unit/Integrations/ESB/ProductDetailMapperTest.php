<?php

namespace Tests\Unit\Integrations\ESB;

use App\Integrations\ESB\Mappers\ProductMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\CoreProductDetail;

/**
 * GET /product/{productID} onto the canonical ProductDetail ([ESB-026]).
 */
class ProductDetailMapperTest extends TestCase
{
    #[Test]
    public function the_detail_maps_its_header(): void
    {
        $product = (new ProductMapper)->fromDetail(CoreProductDetail::detailResponse()['result']);

        $this->assertNotNull($product);
        $this->assertSame('esb', $product->provider);
        $this->assertSame(50, $product->id);
        $this->assertSame('BB-001', $product->code);
        $this->assertSame('Beras Premium', $product->name);
        $this->assertSame('Bahan Baku', $product->categoryName);
        $this->assertSame('Beras', $product->subCategoryName);
        $this->assertNull($product->bomId);
        $this->assertTrue($product->purchasable);
        $this->assertFalse($product->saleable);
        $this->assertTrue($product->vat);
        $this->assertFalse($product->luxury);
        $this->assertSame(2.5, $product->receiptTolerance);
        $this->assertNull($product->coretaxCode);
        $this->assertNull($product->notes);
        $this->assertTrue($product->active);
    }

    #[Test]
    public function the_detail_maps_its_units(): void
    {
        $product = (new ProductMapper)->fromDetail(CoreProductDetail::detailResponse()['result']);

        $this->assertNotNull($product);
        $this->assertCount(2, $product->units);

        [$base, $sack] = $product->units;
        $this->assertSame(501, $base->productDetailId);
        $this->assertSame('KG', $base->uomName);
        $this->assertTrue($base->isBase);
        $this->assertTrue($base->stocked);
        $this->assertSame('BRS-PRM-KG', $base->sku);
        $this->assertSame(15000.5, $base->basePrice);

        $this->assertSame(25.0, $sack->qty);
        $this->assertFalse($sack->isBase);
        $this->assertNull($sack->sku);
    }

    #[Test]
    public function units_that_are_not_objects_or_have_no_id_are_skipped(): void
    {
        $product = (new ProductMapper)->fromDetail(CoreProductDetail::detailResponse([
            'productDetails' => ['oops', CoreProductDetail::unit(['productDetailID' => 0]), CoreProductDetail::unit()],
        ])['result']);

        $this->assertNotNull($product);
        $this->assertCount(1, $product->units);
    }

    #[Test]
    public function a_detail_without_an_id_is_no_product(): void
    {
        $this->assertNull((new ProductMapper)->fromDetail(['productID' => 0]));
        $this->assertNull((new ProductMapper)->fromDetail([]));
    }
}
