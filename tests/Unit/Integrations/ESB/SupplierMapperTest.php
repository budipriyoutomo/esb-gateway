<?php

namespace Tests\Unit\Integrations\ESB;

use App\Integrations\ESB\Mappers\SupplierMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\CoreSupplier;

class SupplierMapperTest extends TestCase
{
    #[Test]
    public function a_row_maps_onto_a_summary(): void
    {
        $supplier = (new SupplierMapper)->summaryFromRow(CoreSupplier::row());

        $this->assertSame(10, $supplier->id);
        $this->assertSame('SUP-001', $supplier->code);
        $this->assertSame('PT Sumber Pangan', $supplier->name);
        $this->assertSame('Bahan Pokok', $supplier->categoryName);
        $this->assertSame('Andi', $supplier->contactPerson);
        $this->assertSame(30, $supplier->creditDays);
        $this->assertTrue($supplier->vatSubject);
        // A boolean in supplier rows, not 1/0.
        $this->assertTrue($supplier->active);
    }

    #[Test]
    public function the_detail_maps_contacts_banks_and_products(): void
    {
        $supplier = (new SupplierMapper)->fromDetail(CoreSupplier::detailResponse()['result']);

        $this->assertNotNull($supplier);
        $this->assertSame('Jakarta', $supplier->city);
        $this->assertSame('01.234.567.8-901.000', $supplier->npwp);
        $this->assertNull($supplier->phone2);
        $this->assertSame('2101', $supplier->payableCoaNo);
        $this->assertNull($supplier->advancePurchaseCoaNo);
        $this->assertTrue($supplier->usedInTransactions);

        $this->assertCount(1, $supplier->contacts);
        $this->assertSame('andi@sumberpangan.test', $supplier->contacts[0]->email);
        $this->assertTrue($supplier->contacts[0]->isDefault);

        $this->assertCount(1, $supplier->products);
        $this->assertSame('SP-BRS-01', $supplier->products[0]->supplierProductCode);
    }

    #[Test]
    public function only_the_last_four_digits_of_an_account_number_are_kept(): void
    {
        $supplier = (new SupplierMapper)->fromDetail(CoreSupplier::detailResponse()['result']);

        $this->assertNotNull($supplier);
        $account = $supplier->bankAccounts[0];
        $this->assertSame('BCA', $account->bankName);
        $this->assertSame('•••• 6789', $account->maskedAccountNumber);
        $this->assertStringNotContainsString('1234566789', json_encode($supplier, JSON_UNESCAPED_UNICODE));
    }

    #[Test]
    public function a_short_or_missing_account_number_is_fully_masked(): void
    {
        $supplier = (new SupplierMapper)->fromDetail(CoreSupplier::detailResponse([
            'supplierBank' => [
                ['bankID' => 3, 'bankName' => 'BCA', 'bankAccountName' => 'X', 'bankAccountNumber' => '123'],
                ['bankID' => 4, 'bankName' => 'BRI', 'bankAccountName' => 'Y', 'bankAccountNumber' => ''],
            ],
        ])['result']);

        $this->assertNotNull($supplier);
        $this->assertSame('••••', $supplier->bankAccounts[0]->maskedAccountNumber);
        $this->assertNull($supplier->bankAccounts[1]->maskedAccountNumber);
    }

    #[Test]
    public function vendor_image_urls_are_not_carried(): void
    {
        $supplier = (new SupplierMapper)->fromDetail(CoreSupplier::detailResponse()['result']);

        $this->assertStringNotContainsString('cdn.example.test', (string) json_encode($supplier));
    }

    #[Test]
    public function nested_rows_that_are_not_objects_are_skipped(): void
    {
        $supplier = (new SupplierMapper)->fromDetail(CoreSupplier::detailResponse([
            'supplierPic' => ['oops', null],
            'supplierBank' => 'not a list',
            'supplierProduct' => null,
        ])['result']);

        $this->assertNotNull($supplier);
        $this->assertSame([], $supplier->contacts);
        $this->assertSame([], $supplier->bankAccounts);
        $this->assertSame([], $supplier->products);
    }

    #[Test]
    public function a_detail_without_an_id_is_no_supplier(): void
    {
        $this->assertNull((new SupplierMapper)->fromDetail(['supplierID' => 0]));
    }
}
