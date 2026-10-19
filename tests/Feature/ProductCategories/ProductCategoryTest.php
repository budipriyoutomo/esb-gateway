<?php

namespace Tests\Feature\ProductCategories;

use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\MasterData\ReadOnlyMasterDataTestCase;
use Tests\Fixtures\CoreProductCategory;

/**
 * ESB Core product categories, read-only ([ESB-021]).
 */
class ProductCategoryTest extends ReadOnlyMasterDataTestCase
{
    protected function resource(): string
    {
        return 'product-categories';
    }

    protected function listPath(): string
    {
        return '/product/category';
    }

    protected function detailPath(): string
    {
        return '/product/category';
    }

    protected function sampleId(): int
    {
        return 3;
    }

    protected function idField(): string
    {
        return 'categoryID';
    }

    protected function operation(): string
    {
        return 'product-category';
    }

    protected function listProp(): string
    {
        return 'categories';
    }

    protected function detailProp(): string
    {
        return 'category';
    }

    protected function listResponse(int $count = 1, int $page = 1): array
    {
        return CoreProductCategory::listResponse($count, $page);
    }

    protected function detailResponse(array $overrides = []): array
    {
        return CoreProductCategory::detailResponse($overrides);
    }

    protected function filterCase(): array
    {
        return [
            ['search' => 'bahan', 'categoryTypeId' => 1, 'flagActive' => 1],
            ['search' => 'bahan', 'categoryTypeId' => 1, 'flagActive' => 1],
            'indexSearch=bahan&categoryTypeID=1&flagActive=1',
        ];
    }

    #[Test]
    public function list_rows_carry_the_category_type(): void
    {
        Http::fake(['*' => Http::response(CoreProductCategory::listResponse())]);

        $this->actingAs($this->operator())
            ->get(route('product-categories.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('categories.0.id', 3)
                ->where('categories.0.name', 'Bahan Baku')
                ->where('categories.0.typeId', 1)
                ->where('categories.0.typeName', 'Inventory')
                ->where('categories.0.notes', null)
                ->where('categories.0.active', true));
    }

    #[Test]
    public function an_unknown_category_type_is_refused(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('product-categories.index', ['categoryTypeId' => 9]))
            ->assertSessionHasErrors('categoryTypeId');

        Http::assertNothingSent();
    }

    #[Test]
    public function the_detail_carries_its_accounts_and_names_its_type(): void
    {
        Http::fake(['*' => Http::response(CoreProductCategory::detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('product-categories.show', 3))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('category.code', 'BB')
                ->where('category.typeId', 1)
                // The detail has no type name; it comes from the vendor's legend.
                ->where('category.typeName', 'Inventory')
                ->where('category.coaNo', '1301')
                ->where('category.cogsCoaNo', '5101')
                ->where('category.accrualBasisCoaNo', null)
                ->where('category.depreciationMonths', null)
                ->where('category.editedBy', 'budi'));
    }
}
