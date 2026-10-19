<?php

namespace Tests\Unit\Integrations\ESB;

use App\Domain\Canonical\LookupKind;
use App\Domain\Canonical\LookupOption;
use App\Integrations\ESB\Mappers\LookupMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\CoreLookup;

class LookupMapperTest extends TestCase
{
    private function map(LookupKind $kind, array $rows): array
    {
        return (new LookupMapper)->optionsFrom($kind, $rows);
    }

    #[Test]
    public function a_branch_becomes_an_option_keyed_by_its_id(): void
    {
        $options = $this->map(LookupKind::Branches, CoreLookup::branches());

        $this->assertCount(3, $options);
        $this->assertEquals(new LookupOption(value: '1', label: 'Jakarta Pusat', code: 'JKT'), $options[0]);
    }

    #[Test]
    public function an_empty_supplier_code_is_no_code(): void
    {
        $options = $this->map(LookupKind::Suppliers, CoreLookup::suppliers());

        $this->assertSame('SUP-001', $options[0]->code);
        $this->assertNull($options[1]->code);
        $this->assertSame('CV Maju Jaya', $options[1]->label);
    }

    #[Test]
    public function a_location_carries_its_branch(): void
    {
        $option = $this->map(LookupKind::Locations, CoreLookup::locations())[0];

        $this->assertSame('100', $option->value);
        $this->assertSame('Gudang Utama', $option->label);
        $this->assertSame(['branchId' => 1], $option->meta);
    }

    #[Test]
    public function an_account_is_keyed_by_its_coa_number(): void
    {
        $coa = $this->map(LookupKind::CoaAccounts, CoreLookup::coaAccounts())[0];
        $cash = $this->map(LookupKind::CashBankAccounts, CoreLookup::cashBankAccounts())[0];

        $this->assertEquals(new LookupOption(value: '6101', label: 'Biaya Listrik', code: '6101'), $coa);
        $this->assertSame('1101', $cash->value);
        $this->assertSame(['branchId' => 1], $cash->meta);
    }

    #[Test]
    public function a_status_is_keyed_by_its_id(): void
    {
        $option = $this->map(LookupKind::Statuses, CoreLookup::statuses())[1];

        $this->assertEquals(new LookupOption(value: '2', label: 'Authorized'), $option);
    }

    #[Test]
    public function a_transfer_product_is_keyed_by_product_detail_and_carries_its_unit(): void
    {
        $option = $this->map(LookupKind::TransferProducts, CoreLookup::transferProducts())[0];

        $this->assertSame('501', $option->value);
        $this->assertSame('Beras Premium', $option->label);
        $this->assertSame('BB-001', $option->code);
        $this->assertSame(['productId' => 50, 'uomName' => 'KG', 'stockQty' => 120.5], $option->meta);
    }

    #[Test]
    public function rows_without_an_id_or_a_label_are_dropped(): void
    {
        // The vendor's examples are zero-valued: 0 / "" mean "not there".
        $options = $this->map(LookupKind::Branches, [
            ['branchID' => 0, 'branchCode' => '', 'branchName' => ''],
            ['branchID' => 5, 'branchCode' => 'X', 'branchName' => ''],
            ['branchID' => 6, 'branchCode' => 'Y', 'branchName' => '  '],
            ['branchCode' => 'Z', 'branchName' => 'No id'],
            'not a row',
            ['branchID' => '7', 'branchCode' => 'OK', 'branchName' => 'Numeric string id'],
        ]);

        $this->assertSame(['7'], array_map(fn (LookupOption $o) => $o->value, $options));
    }

    #[Test]
    public function an_account_without_a_number_is_dropped(): void
    {
        $options = $this->map(LookupKind::CoaAccounts, [
            ['coaNo' => '', 'description' => 'No number'],
            ['coaNo' => '6101', 'description' => 'Biaya Listrik'],
        ]);

        $this->assertCount(1, $options);
    }
}
