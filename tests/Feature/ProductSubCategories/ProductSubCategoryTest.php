<?php

namespace Tests\Feature\ProductSubCategories;

use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\MasterData\ReadOnlyMasterDataTestCase;
use Tests\Fixtures\CoreProductSubCategory;

/**
 * ESB Core product sub-categories, read-only ([ESB-022]).
 */
class ProductSubCategoryTest extends ReadOnlyMasterDataTestCase
{
    protected function resource(): string
    {
        return 'product-sub-categories';
    }

    protected function listPath(): string
    {
        return '/product/sub-category';
    }

    protected function detailPath(): string
    {
        return '/product/sub-category';
    }

    protected function sampleId(): int
    {
        return 7;
    }

    protected function idField(): string
    {
        return 'subCategoryID';
    }

    protected function operation(): string
    {
        return 'product-sub-category';
    }

    protected function listProp(): string
    {
        return 'subCategories';
    }

    protected function detailProp(): string
    {
        return 'subCategory';
    }

    protected function listResponse(int $count = 1, int $page = 1): array
    {
        return CoreProductSubCategory::listResponse($count, $page);
    }

    protected function detailResponse(array $overrides = []): array
    {
        return CoreProductSubCategory::detailResponse($overrides);
    }

    protected function filterCase(): array
    {
        return [
            ['search' => 'beras', 'flagActive' => 1],
            ['search' => 'beras', 'flagActive' => 1],
            'indexSearch=beras&flagActive=1',
        ];
    }

    #[Test]
    public function list_rows_carry_the_dead_stock_threshold(): void
    {
        Http::fake(['*' => Http::response(CoreProductSubCategory::listResponse())]);

        $this->actingAs($this->operator())
            ->get(route('product-sub-categories.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('subCategories.0.id', 7)
                ->where('subCategories.0.name', 'Beras')
                ->where('subCategories.0.deadStockDays', 90)
                ->where('subCategories.0.active', true));
    }

    #[Test]
    public function the_detail_carries_its_notes(): void
    {
        Http::fake(['*' => Http::response(CoreProductSubCategory::detailResponse())]);

        $this->actingAs($this->operator())
            ->get(route('product-sub-categories.show', 7))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('subCategory.notes', 'Semua jenis beras')
                ->where('subCategory.createdBy', 'admin'));
    }
}
