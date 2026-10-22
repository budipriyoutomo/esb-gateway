<?php

namespace Tests\Feature\Suppliers;

use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\MasterData\ReadOnlyMasterDataTestCase;
use Tests\Fixtures\CoreSupplier;

/**
 * ESB Core suppliers, read-only ([ESB-024]).
 */
class SupplierTest extends ReadOnlyMasterDataTestCase
{
    protected function resource(): string
    {
        return 'suppliers';
    }

    protected function listPath(): string
    {
        return '/supplier';
    }

    protected function detailPath(): string
    {
        return '/supplier';
    }

    protected function sampleId(): int
    {
        return 10;
    }

    protected function idField(): string
    {
        return 'supplierID';
    }

    protected function operation(): string
    {
        return 'supplier';
    }

    protected function listProp(): string
    {
        return 'suppliers';
    }

    protected function detailProp(): string
    {
        return 'supplier';
    }

    protected function listResponse(int $count = 1, int $page = 1): array
    {
        return CoreSupplier::listResponse($count, $page);
    }

    protected function detailResponse(array $overrides = []): array
    {
        return CoreSupplier::detailResponse($overrides);
    }

    protected function filterCase(): array
    {
        return [
            ['search' => 'pangan', 'flagActive' => 1],
            ['search' => 'pangan', 'flagActive' => 1],
            'indexSearch=pangan&flagActive=1',
        ];
    }

    #[Test]
    public function list_rows_carry_the_supplier_fields(): void
    {
        Http::fake(['*' => Http::response(CoreSupplier::listResponse())]);

        $this->actingAs($this->operator())
            ->get(route('suppliers.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('suppliers.0.code', 'SUP-001')
                ->where('suppliers.0.name', 'PT Sumber Pangan')
                ->where('suppliers.0.categoryName', 'Bahan Pokok')
                ->where('suppliers.0.creditDays', 30)
                ->where('suppliers.0.active', true));
    }

    #[Test]
    public function the_detail_is_one_call_with_contacts_and_banks(): void
    {
        Http::fake(['*' => Http::response(CoreSupplier::detailResponse())]);

        $response = $this->actingAs($this->operator())->get(route('suppliers.show', 10));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('supplier.contacts.0.name', 'Andi')
            ->where('supplier.bankAccounts.0.accountNumber', '1234566789')
            ->where('supplier.products.0.productName', 'Beras Premium'));

        Http::assertSentCount(1);
    }
}
