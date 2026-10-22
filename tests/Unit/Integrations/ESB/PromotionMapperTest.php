<?php

namespace Tests\Unit\Integrations\ESB;

use App\Integrations\ESB\Mappers\PromotionMapper;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\OmsPromotion;
use Tests\TestCase;

/**
 * ESB OMS promotions onto the canonical shape ([ESB-049]): the payment
 * method read as one object or a list, the scope by kind, a row without an
 * id dropped.
 */
class PromotionMapperTest extends TestCase
{
    #[Test]
    public function it_maps_a_promotion(): void
    {
        $promotions = (new PromotionMapper)->fromRows([
            OmsPromotion::row(),
            OmsPromotion::row(['promotionID' => 0]),
            'not a row',
        ]);

        $this->assertCount(1, $promotions);
        $promotion = $promotions[0];
        $this->assertSame('esb', $promotion->provider);
        $this->assertSame(52, $promotion->id);
        $this->assertSame('PROMOESO50K', $promotion->code);
        $this->assertSame('PROMO DISCOUNT (Rp) ESO', $promotion->typeName);
        $this->assertSame(50000.0, $promotion->discount);
        $this->assertSame(0.0, $promotion->minSubtotal);
        $this->assertSame('2023-06-01 00:00:00', $promotion->startAt);
        $this->assertTrue($promotion->shownOnEso);
        $this->assertSame([['id' => 2, 'code' => 'OUT1', 'name' => 'OUTLET Tangerang 1']], $promotion->branches);
        $this->assertSame(['OVO (Xendit)', 'QRIS (ESB)'], $promotion->selfOrderPaymentMethods);
        $this->assertSame(['DANA'], $promotion->paymentMethods);
        $this->assertSame(['menuCategories' => 0, 'menuCategoryDetails' => 0, 'menus' => 0], $promotion->scope);
    }

    #[Test]
    public function the_payment_methods_and_scope_are_read_from_lists_too(): void
    {
        $promotion = (new PromotionMapper)->fromRows([OmsPromotion::row([
            'paymentMethod' => [['paymentMethodID' => 1, 'paymentMethodName' => 'CASH'], ['paymentMethodName' => '']],
            'promotionCategory' => [
                ['menuCategoryID' => 1, 'menuCategoryDetailID' => 0, 'menuID' => 0],
                ['menuCategoryID' => 0, 'menuCategoryDetailID' => 0, 'menuID' => 5564],
            ],
            'flagShow' => 0,
        ])])[0];

        $this->assertSame(['CASH'], $promotion->paymentMethods);
        $this->assertSame(['menuCategories' => 1, 'menuCategoryDetails' => 0, 'menus' => 1], $promotion->scope);
        $this->assertFalse($promotion->shownOnEso);
    }
}
