<?php

namespace Tests\Feature\SupplierCategories;

use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\MasterData\ReadOnlyMasterDataTestCase;
use Tests\Fixtures\CoreSupplierCategory;

/**
 * ESB Core supplier categories, read-only ([ESB-023]).
 */
class SupplierCategoryTest extends ReadOnlyMasterDataTestCase
{
    protected function resource(): string
    {
        return 'supplier-categories';
    }

    protected function listPath(): string
    {
        return '/supplier/category/list';
    }

    protected function detailPath(): string
    {
        return '/supplier/category';
    }

    protected function sampleId(): int
    {
        return 5;
    }

    protected function idField(): string
    {
        return 'supplierCategoryID';
    }

    protected function operation(): string
    {
        return 'supplier-category';
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
        return CoreSupplierCategory::listResponse($count, $page);
    }

    protected function detailResponse(array $overrides = []): array
    {
        return CoreSupplierCategory::detailResponse($overrides);
    }

    protected function filterCase(): array
    {
        // The vendor documents no free-text search here; the name filter is
        // what the gateway's search maps onto.
        return [
            ['search' => 'pokok', 'flagActive' => 1],
            ['search' => 'pokok', 'flagActive' => 1],
            'supplierCategoryName=pokok&flagActive=1',
        ];
    }

    #[Test]
    public function list_rows_carry_name_and_status(): void
    {
        Http::fake(['*' => Http::response(CoreSupplierCategory::listResponse())]);

        $this->actingAs($this->operator())
            ->get(route('supplier-categories.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('categories.0.id', 5)
                ->where('categories.0.name', 'Bahan Pokok')
                ->where('categories.0.active', true));
    }
}
