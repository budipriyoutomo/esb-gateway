<?php

namespace Tests\Feature\ProductCategories;

use Tests\Feature\MasterData\MasterDataWriteTestCase;

/**
 * Deleting, deactivating and restoring product categories ([ESB-021], S2).
 */
class ProductCategoryWriteTest extends MasterDataWriteTestCase
{
    protected function resource(): string
    {
        return 'product-categories';
    }

    protected function upstreamPath(): string
    {
        return '/product/category/3';
    }

    protected function sampleId(): int
    {
        return 3;
    }

    protected function prefix(): string
    {
        return 'product-category';
    }

    protected function operations(): array
    {
        return [
            'delete' => ['DELETE', 'DELETE', ''],
            'deactivate' => ['PATCH', 'PATCH', '/inactive'],
            'restore' => ['PATCH', 'PATCH', '/restore'],
        ];
    }
}
