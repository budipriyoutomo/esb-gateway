<?php

namespace Tests\Unit\Integrations\ESB;

use App\Domain\Canonical\ProductDraft;
use App\Domain\Canonical\ProductUnitDraft;
use App\Integrations\ESB\Mappers\ProductMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\CoreProductDetail;

/**
 * The body of POST /product and PUT /product/{productID} ([ESB-031]).
 * PUT replaces the product, so what the form does not edit is carried over
 * from the product as the ERP has it.
 */
class ProductRequestMapperTest extends TestCase
{
    private function draft(?int $baseDetailId = null): ProductDraft
    {
        return new ProductDraft(
            name: 'Beras Premium',
            categoryId: 3,
            subCategoryId: 7,
            code: 'BB-001',
            notes: null,
            purchasable: true,
            saleable: false,
            requestable: true,
            vat: true,
            luxury: false,
            receiptTolerance: null,
            units: [
                new ProductUnitDraft(uomId: 4, qty: 1, isBase: true, purchasable: true, saleable: false, stocked: true, transferable: true, sku: 'BRS-KG', basePrice: 15000, weight: 1, active: true, productDetailId: $baseDetailId),
                new ProductUnitDraft(uomId: 9, qty: 25, isBase: false, purchasable: true, saleable: false, stocked: true, transferable: true, sku: null, basePrice: null, weight: null, active: true),
            ],
        );
    }

    #[Test]
    public function a_new_product_sends_every_documented_field_with_the_vendors_none(): void
    {
        $this->assertSame([
            'productName' => 'Beras Premium',
            'productCode' => 'BB-001',
            'categoryID' => 3,
            'subCategoryID' => 7,
            'notes' => '',
            'purchasable' => true,
            'saleable' => false,
            'requestable' => true,
            'vat' => true,
            'flagLuxuryItem' => 0,
            'receiptTolerance' => 0,
            'productDetails' => [
                ['uomID' => 4, 'qty' => 1, 'isBase' => true, 'isPurchase' => true, 'isSales' => false, 'isStock' => true, 'isTransfer' => true, 'sku' => 'BRS-KG', 'basePrice' => 15000, 'weight' => 1, 'flagActive' => true],
                ['uomID' => 9, 'qty' => 25, 'isBase' => false, 'isPurchase' => true, 'isSales' => false, 'isStock' => true, 'isTransfer' => true, 'sku' => '', 'basePrice' => 0, 'weight' => 0, 'flagActive' => true],
            ],
        ], (new ProductMapper)->requestFrom($this->draft()));
    }

    #[Test]
    public function an_update_carries_over_what_the_form_does_not_edit(): void
    {
        $mapper = new ProductMapper;
        $current = $mapper->fromDetail(CoreProductDetail::detailResponse([
            'bomID' => 8,
            'coretaxProductCodeID' => 77,
            'customFields' => ['field1' => 'Organik', 'field2' => null],
            'productDetails' => [
                CoreProductDetail::unit(['menuID' => 300, 'cubication' => 0.5]),
            ],
        ])['result']);
        $this->assertNotNull($current);

        $body = $mapper->requestFrom($this->draft(baseDetailId: 501), $current);

        $this->assertSame(8, $body['bomID']);
        $this->assertSame(77, $body['coretaxProductCodeID']);
        $this->assertSame(['field1' => 'Organik', 'field2' => null], $body['customFields']);
        // The existing unit keeps its id, menu and cubication; the new one has none.
        $this->assertSame(501, $body['productDetails'][0]['productDetailID']);
        $this->assertSame(300, $body['productDetails'][0]['menuID']);
        $this->assertSame(0.5, $body['productDetails'][0]['cubication']);
        $this->assertArrayNotHasKey('productDetailID', $body['productDetails'][1]);
        $this->assertArrayNotHasKey('menuID', $body['productDetails'][1]);
    }

    #[Test]
    public function the_detail_keeps_the_ids_an_update_must_send_back(): void
    {
        $product = (new ProductMapper)->fromDetail(CoreProductDetail::detailResponse([
            'coretaxProductCodeID' => 77,
            'customFields' => ['field1' => 'Organik', 'field2' => null, 'field3' => 5],
            'productDetails' => [CoreProductDetail::unit(['menuID' => 300, 'cubication' => 0.5])],
        ])['result']);

        $this->assertNotNull($product);
        $this->assertSame(77, $product->coretaxCodeId);
        // Only text (or nothing) is a custom field value.
        $this->assertSame(['field1' => 'Organik', 'field2' => null], $product->customFields);
        $this->assertSame(300, $product->units[0]->menuId);
        $this->assertSame(0.5, $product->units[0]->cubication);
    }
}
