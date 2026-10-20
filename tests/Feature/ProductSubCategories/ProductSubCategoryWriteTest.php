<?php

namespace Tests\Feature\ProductSubCategories;

use Tests\Feature\MasterData\MasterDataWriteTestCase;

/**
 * Deleting, deactivating and restoring product sub-categories ([ESB-022], S2).
 */
class ProductSubCategoryWriteTest extends MasterDataWriteTestCase
{
    protected function resource(): string
    {
        return 'product-sub-categories';
    }

    protected function upstreamPath(): string
    {
        return '/product/sub-category/7';
    }

    protected function sampleId(): int
    {
        return 7;
    }

    protected function prefix(): string
    {
        return 'product-sub-category';
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
