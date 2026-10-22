<?php

namespace Tests\Unit\Integrations\ESB;

use App\Integrations\ESB\Mappers\MenuCategoryMapper;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\OmsMenuCategory;
use Tests\TestCase;

/**
 * ESB OMS menu categories onto the canonical shape ([ESB-047]): "" is none,
 * a colour is kept only when it is a hex colour, a row without an id is
 * dropped.
 */
class MenuCategoryMapperTest extends TestCase
{
    #[Test]
    public function it_maps_a_category_with_its_details(): void
    {
        $categories = (new MenuCategoryMapper)->fromRows([
            OmsMenuCategory::row(),
            OmsMenuCategory::row(['menuCategoryID' => 0]),
            'not a row',
        ]);

        $this->assertCount(1, $categories);
        $category = $categories[0];
        $this->assertSame('esb', $category->provider);
        $this->assertSame(OmsMenuCategory::ID, $category->id);
        $this->assertSame('Food-Int', $category->name);
        $this->assertSame('FINT', $category->code);
        $this->assertSame('Sales - Food', $category->salesAccount);
        $this->assertSame('COGS - Food', $category->cogsAccount);
        $this->assertSame('Food Discount', $category->discountAccount);
        $this->assertSame('Makanan Cita Rasa Indonesia', $category->description);
        $this->assertSame('Active', $category->statusName);
        $this->assertNull($category->buttonColor);

        $this->assertCount(1, $category->details);
        $detail = $category->details[0];
        $this->assertSame(34, $detail->id);
        $this->assertSame('Main Course-Int', $detail->name);
        $this->assertNull($detail->code);
        $this->assertSame(1.0, $detail->maxOrderQty);
        $this->assertSame('#a4ff70', $detail->buttonColor);
        $this->assertNull($detail->description);
    }

    #[Test]
    public function a_colour_that_is_not_hex_and_a_detail_without_an_id_are_dropped(): void
    {
        $category = (new MenuCategoryMapper)->fromRows([OmsMenuCategory::row([
            'buttonColor' => 'red;background:url(x)',
            'menuCategoryDetails' => [['menuCategoryDetailID' => null, 'menuCategoryDetailName' => 'X']],
        ])])[0];

        $this->assertNull($category->buttonColor);
        $this->assertSame([], $category->details);
    }
}
