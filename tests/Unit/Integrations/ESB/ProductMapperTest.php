<?php

namespace Tests\Unit\Integrations\ESB;

use App\Integrations\ESB\Mappers\ProductMapper;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Maps a row from GET /product/list onto our own shape.
 *
 * Fields are transcribed from the vendor documentation for this one operation.
 * ESB responses are untrusted input, so a row missing a field it documents as
 * required must not blow up a page render.
 */
class ProductMapperTest extends TestCase
{
    /**
     * The vendor's own example row.
     *
     * @return array<string, mixed>
     */
    private function vendorRow(): array
    {
        return [
            'productID' => 75,
            'productName' => 'Air mineral',
            'productCode' => 'Air_mineral',
            'categoryID' => 14,
            'categoryName' => 'Kategori Test',
            'subCategoryID' => 10,
            'subCategoryName' => 'BUDGET INV',
            'bomID' => null,
            'bomName' => null,
            'categoryTypeID' => 1,
            'categoryTypeName' => 'Inventory',
            'flagActive' => 1,
        ];
    }

    #[Test]
    public function it_maps_the_documented_fields(): void
    {
        $product = app(ProductMapper::class)->fromRow($this->vendorRow());

        $this->assertSame(75, $product->id);
        $this->assertSame('Air mineral', $product->name);
        $this->assertSame('Air_mineral', $product->code);
        $this->assertSame(14, $product->categoryId);
        $this->assertSame('Kategori Test', $product->categoryName);
        $this->assertSame(10, $product->subCategoryId);
        $this->assertSame('BUDGET INV', $product->subCategoryName);
        $this->assertSame(1, $product->categoryTypeId);
        $this->assertSame('Inventory', $product->categoryTypeName);
    }

    #[Test]
    public function flag_active_becomes_a_boolean(): void
    {
        $mapper = app(ProductMapper::class);

        $this->assertTrue($mapper->fromRow($this->vendorRow())->isActive);

        $this->assertFalse($mapper->fromRow([...$this->vendorRow(), 'flagActive' => 0])->isActive);
    }

    #[Test]
    public function a_product_without_a_bill_of_material_maps_to_nulls(): void
    {
        // bomID and bomName are null in the vendor's own example, even though
        // the documentation marks them required.
        $product = app(ProductMapper::class)->fromRow($this->vendorRow());

        $this->assertNull($product->bomId);
        $this->assertNull($product->bomName);
    }

    #[Test]
    public function a_row_missing_documented_fields_still_maps(): void
    {
        // ESB responses are untrusted input (docs/10-security.md). A row that
        // omits a documented field must not take down the page.
        $product = app(ProductMapper::class)->fromRow(['productID' => 9]);

        $this->assertSame(9, $product->id);
        $this->assertSame('', $product->name);
        $this->assertNull($product->categoryId);
        $this->assertFalse($product->isActive);
    }

    #[Test]
    public function it_serializes_without_any_esb_detail(): void
    {
        $encoded = json_encode(app(ProductMapper::class)->fromRow($this->vendorRow()));

        $this->assertIsString($encoded);

        // Nothing here should hint at the upstream host or its field names --
        // the browser sees our shape, not the ESB's (ADR-006).
        $this->assertStringNotContainsString('esb.co.id', $encoded);
        $this->assertStringNotContainsString('flagActive', $encoded);
        $this->assertStringNotContainsString('productID', $encoded);
    }
}
