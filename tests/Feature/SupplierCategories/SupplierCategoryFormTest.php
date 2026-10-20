<?php

namespace Tests\Feature\SupplierCategories;

use Tests\Feature\MasterData\MasterDataFormTestCase;
use Tests\Fixtures\CoreSupplierCategory;

/**
 * Creating and updating supplier categories ([ESB-030], S3). The create returns the new id.
 */
class SupplierCategoryFormTest extends MasterDataFormTestCase
{
    protected function resource(): string
    {
        return 'supplier-categories';
    }

    protected function prefix(): string
    {
        return 'supplier-category';
    }

    protected function pageComponent(): string
    {
        return 'supplier-categories/form';
    }

    protected function recordProp(): string
    {
        return 'category';
    }

    protected function sampleId(): int
    {
        return 5;
    }

    /** The vendor's create path has a trailing slash. */
    protected function createPath(): string
    {
        return '/supplier/category/';
    }

    protected function updatePath(): string
    {
        return '/supplier/category/5';
    }

    protected function detailResponse(): array
    {
        return CoreSupplierCategory::detailResponse();
    }

    protected function validInput(): array
    {
        return ['name' => 'Bahan Pokok'];
    }

    protected function expectedBody(): array
    {
        return ['supplierCategoryName' => 'Bahan Pokok'];
    }

    protected function createResult(): mixed
    {
        return ['supplierCategoryID' => 12];
    }

    protected function createdLanding(): string
    {
        return route('supplier-categories.show', 12);
    }

    protected function invalidInputs(): array
    {
        return [
            [['name' => ''], 'name'],
            [['name' => str_repeat('a', 51)], 'name'],
            // The vendor accepts ASCII only.
            [['name' => 'Bahan Pokok é'], 'name'],
        ];
    }

    protected function vendorFieldError(): array
    {
        return ['supplierCategoryName', 'name'];
    }
}
