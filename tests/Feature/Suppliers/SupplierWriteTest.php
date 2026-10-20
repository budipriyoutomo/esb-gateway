<?php

namespace Tests\Feature\Suppliers;

use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\MasterData\MasterDataWriteTestCase;

/**
 * Deactivating and restoring suppliers ([ESB-024], S2). The vendor's
 * DELETE /supplier/{id} deactivates; there is no permanent delete.
 */
class SupplierWriteTest extends MasterDataWriteTestCase
{
    protected function resource(): string
    {
        return 'suppliers';
    }

    protected function upstreamPath(): string
    {
        return '/supplier/10';
    }

    protected function sampleId(): int
    {
        return 10;
    }

    protected function prefix(): string
    {
        return 'supplier';
    }

    protected function operations(): array
    {
        return [
            'deactivate' => ['PATCH', 'DELETE', ''],
            'restore' => ['PATCH', 'PATCH', '/restore'],
        ];
    }

    #[Test]
    public function suppliers_cannot_be_deleted_permanently_here(): void
    {
        $this->assertFalse(app('router')->has('suppliers.destroy'));
    }
}
