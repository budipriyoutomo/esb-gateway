<?php

namespace Tests\Unit\Integrations\ESB;

use App\Domain\Canonical\MasterMenu;
use App\Integrations\ESB\Mappers\MasterMenuMapper;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\OmsGetMenu;
use Tests\TestCase;

/**
 * ESB OMS Get Menu rows onto the canonical MasterMenu ([ESB-013]).
 */
class MasterMenuMapperTest extends TestCase
{
    private function map(array $row): MasterMenu
    {
        return (new MasterMenuMapper)->fromRow($row);
    }

    #[Test]
    public function it_maps_the_menu_header(): void
    {
        $menu = $this->map(OmsGetMenu::row());

        $this->assertSame('esb', $menu->provider);
        $this->assertSame(5564, $menu->id);
        $this->assertSame('PS01', $menu->code);
        $this->assertSame('Paket Separate', $menu->name);
        $this->assertSame('Paket Separate', $menu->shortName);
        $this->assertSame('Food - RX - Snack', $menu->categoryName);
        $this->assertNull($menu->bomName);
        $this->assertTrue($menu->active);
        $this->assertSame('Yes, VAT', $menu->taxLabel);
        $this->assertTrue($menu->otherTax);
        $this->assertFalse($menu->openPrice);
        $this->assertTrue($menu->printZeroValue);
        $this->assertSame('0', $menu->zeroValueText);
        $this->assertSame('Sales - Food', $menu->salesAccount);
        $this->assertSame('COGS - Food', $menu->cogsAccount);
        $this->assertSame('Food Discount', $menu->discountAccount);
        $this->assertNull($menu->description);
        $this->assertTrue($menu->separatePrintPackage);
        $this->assertFalse($menu->separateTaxCalculation);
    }

    #[Test]
    public function it_maps_prices_per_template(): void
    {
        $templates = $this->map(OmsGetMenu::row())->templates;

        $this->assertCount(2, $templates);
        $this->assertSame(18, $templates[0]->templateId);
        $this->assertSame('RX Rest', $templates[0]->templateName);
        $this->assertTrue($templates[0]->active);
        $this->assertSame(20000.0, $templates[0]->price);
        $this->assertFalse($templates[1]->active);
    }

    #[Test]
    public function it_maps_package_groups_and_their_items(): void
    {
        $groups = $this->map(OmsGetMenu::row())->packageGroups;

        $this->assertCount(1, $groups);
        $this->assertSame(583, $groups[0]->groupId);
        $this->assertSame('Burger', $groups[0]->name);
        $this->assertSame(0.0, $groups[0]->minQty);
        $this->assertSame(999999.0, $groups[0]->maxQty);
        $this->assertCount(2, $groups[0]->items);
        $this->assertNull($groups[0]->items[0]->code);
        $this->assertSame(5000.0, $groups[0]->items[0]->additionalPrice);
        $this->assertFalse($groups[0]->items[0]->defaultItem);
        $this->assertSame('BGR1R', $groups[0]->items[1]->code);
        $this->assertTrue($groups[0]->items[1]->defaultItem);
    }

    #[Test]
    public function it_maps_extras_icons_tags_and_related_menus(): void
    {
        $menu = $this->map(OmsGetMenu::row());

        $this->assertSame(2649, $menu->extras[0]->extraId);
        $this->assertSame('Beef Burger', $menu->extras[0]->name);
        $this->assertSame(10.0, $menu->extras[0]->maxQty);
        $this->assertSame(1000.0, $menu->extras[0]->price);
        $this->assertSame(['Recommended'], $menu->icons);
        $this->assertSame(['Package'], $menu->tags);
        $this->assertSame(201, $menu->relatedMenus[0]->menuId);
        $this->assertSame('PSN01', $menu->relatedMenus[0]->code);
    }

    #[Test]
    public function vendor_hosted_urls_are_not_carried(): void
    {
        // Image and icon URLs point at vendor hosts. The browser must not be
        // sent to them (ADR-006), so neither reaches the canonical record.
        $json = json_encode($this->map(OmsGetMenu::row()));

        $this->assertIsString($json);
        $this->assertStringNotContainsString('https://', $json);
    }

    #[Test]
    public function malformed_nested_data_is_skipped_rather_than_trusted(): void
    {
        $menu = $this->map(OmsGetMenu::row([
            'flagActive' => 'maybe',
            'menuTemplates' => 'nope',
            'menuPackages' => ['menuGroup' => ['not-a-group', ['menuGroupName' => 'Drink', 'menus' => 'x']]],
            'menuExtras' => [null, ['menuExtraName' => '']],
            'menuIcons' => [['menuIconName' => ''], 'Spicy'],
            'menuTags' => null,
            'relatedMenus' => [['menuName' => 'No id']],
        ]));

        $this->assertNull($menu->active);
        $this->assertSame([], $menu->templates);
        $this->assertCount(1, $menu->packageGroups);
        $this->assertSame([], $menu->packageGroups[0]->items);
        $this->assertSame([], $menu->extras);
        $this->assertSame([], $menu->icons);
        $this->assertSame([], $menu->tags);
        $this->assertSame([], $menu->relatedMenus);
    }
}
