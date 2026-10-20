<?php

namespace Tests\Feature\ProductSubCategories;

use Tests\Feature\MasterData\MasterDataFormTestCase;
use Tests\Fixtures\CoreProductSubCategory;

/**
 * Creating and updating product sub-categories ([ESB-030], S3).
 */
class ProductSubCategoryFormTest extends MasterDataFormTestCase
{
    protected function resource(): string
    {
        return 'product-sub-categories';
    }

    protected function prefix(): string
    {
        return 'product-sub-category';
    }

    protected function pageComponent(): string
    {
        return 'product-sub-categories/form';
    }

    protected function recordProp(): string
    {
        return 'subCategory';
    }

    protected function sampleId(): int
    {
        return 7;
    }

    protected function createPath(): string
    {
        return '/product/sub-category';
    }

    protected function updatePath(): string
    {
        return '/product/sub-category/7';
    }

    protected function detailResponse(): array
    {
        return CoreProductSubCategory::detailResponse();
    }

    protected function validInput(): array
    {
        return ['name' => 'Beras', 'deadStockDays' => 90, 'notes' => 'Semua jenis beras'];
    }

    protected function expectedBody(): array
    {
        return ['subCategoryName' => 'Beras', 'deadStockThreshold' => 90, 'notes' => 'Semua jenis beras'];
    }

    protected function invalidInputs(): array
    {
        return [
            [['name' => ''], 'name'],
            [['name' => str_repeat('a', 51)], 'name'],
            [['deadStockDays' => 0], 'deadStockDays'],
            [['deadStockDays' => null], 'deadStockDays'],
            [['notes' => str_repeat('a', 101)], 'notes'],
        ];
    }

    protected function vendorFieldError(): array
    {
        return ['deadStockThreshold', 'deadStockDays'];
    }
}
