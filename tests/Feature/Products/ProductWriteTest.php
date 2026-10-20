<?php

namespace Tests\Feature\Products;

use Tests\Feature\MasterData\MasterDataWriteTestCase;

/**
 * Deleting and restoring products ([ESB-029], S2). Temporary-product
 * authorize / reject is not built (TODO §2.1).
 */
class ProductWriteTest extends MasterDataWriteTestCase
{
    protected function resource(): string
    {
        return 'products';
    }

    protected function upstreamPath(): string
    {
        return '/product/50';
    }

    protected function sampleId(): int
    {
        return 50;
    }

    protected function prefix(): string
    {
        return 'product';
    }

    protected function operations(): array
    {
        return [
            'delete' => ['DELETE', 'DELETE', ''],
            'restore' => ['PATCH', 'PATCH', '/restore'],
        ];
    }

    /**
     * "list" is a literal vendor path under /product.
     */
    protected function invalidIds(): array
    {
        return ['list', 'abc', '0', '-1', rawurlencode('1/2')];
    }
}
