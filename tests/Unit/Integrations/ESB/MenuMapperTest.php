<?php

namespace Tests\Unit\Integrations\ESB;

use App\Domain\Canonical\Menu;
use App\Integrations\ESB\Mappers\MenuMapper;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\MenuBrowse;
use Tests\TestCase;

/**
 * ESB Core GET /menu/browse rows onto the canonical Menu ([ESB-012]).
 */
class MenuMapperTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $row
     */
    private function map(array $row): Menu
    {
        return (new MenuMapper)->fromRow($row);
    }

    #[Test]
    public function it_maps_a_browse_row(): void
    {
        $menu = $this->map(MenuBrowse::row());

        $this->assertSame('esb', $menu->provider);
        $this->assertSame(5564, $menu->id);
        $this->assertSame('PS01', $menu->code);
        $this->assertSame('Paket Separate', $menu->name);
        $this->assertSame('Paket Sep', $menu->shortName);
        $this->assertSame(16, $menu->categoryId);
        $this->assertSame('Food', $menu->categoryName);
        $this->assertSame(34, $menu->categoryDetailId);
        $this->assertSame('Snack', $menu->categoryDetailName);
        $this->assertNull($menu->notes);
    }

    #[Test]
    public function the_vendors_zero_and_empty_placeholders_become_null(): void
    {
        // The vendor's own example row is all 0 and "".
        $menu = $this->map([
            'categoryDesc' => '',
            'categoryDetailDesc' => '',
            'categoryDetailID' => 0,
            'categoryID' => 0,
            'menuCode' => '',
            'menuID' => 0,
            'menuName' => '',
            'menuShortName' => '',
            'notes' => '',
        ]);

        $this->assertNull($menu->id);
        $this->assertSame('', $menu->code);
        $this->assertSame('', $menu->name);
        $this->assertNull($menu->shortName);
        $this->assertNull($menu->categoryId);
        $this->assertNull($menu->categoryName);
        $this->assertNull($menu->categoryDetailId);
        $this->assertNull($menu->categoryDetailName);
    }

    #[Test]
    public function values_of_the_wrong_type_are_not_trusted(): void
    {
        $menu = $this->map(MenuBrowse::row([
            'menuID' => 'abc',
            'menuCode' => ['PS01'],
            'menuName' => 42,
            'categoryID' => null,
            'categoryDesc' => ['Food'],
        ]));

        $this->assertNull($menu->id);
        $this->assertSame('', $menu->code);
        $this->assertSame('', $menu->name);
        $this->assertNull($menu->categoryId);
        $this->assertNull($menu->categoryName);
    }
}
