<?php

namespace Tests\Unit\Integrations\ESB;

use App\Integrations\ESB\Mappers\CoaAccountMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\CoreCoaAccount;

class CoaAccountMapperTest extends TestCase
{
    #[Test]
    public function rows_are_sorted_into_chart_order(): void
    {
        $accounts = (new CoaAccountMapper)->summariesFromRows(CoreCoaAccount::chart());

        $this->assertSame(['1', '11', '1101', '6', '6101'], array_map(fn ($a) => $a->coaNo, $accounts));
    }

    #[Test]
    public function a_row_maps_onto_a_summary(): void
    {
        $account = (new CoaAccountMapper)->summariesFromRows([CoreCoaAccount::row('1101', 'Kas', 4, 3, ['flagModule' => 1])])[0];

        $this->assertSame('1101', $account->coaNo);
        $this->assertSame('Kas', $account->description);
        $this->assertSame(4, $account->level);
        $this->assertSame('Rp', $account->currency);
        // branchID 0: not tied to a branch.
        $this->assertNull($account->branchId);
        $this->assertTrue($account->moduleAccount);
        $this->assertFalse($account->allowBudget);
        $this->assertTrue($account->active);
    }

    #[Test]
    public function rows_without_a_number_are_dropped_and_odd_levels_are_none(): void
    {
        $accounts = (new CoaAccountMapper)->summariesFromRows([
            CoreCoaAccount::row('', 'No number', 1, 1),
            CoreCoaAccount::row('9', 'Odd level', 9, 2),
            'not a row',
        ]);

        $this->assertCount(1, $accounts);
        $this->assertNull($accounts[0]->level);
    }

    #[Test]
    public function the_detail_carries_its_mappings(): void
    {
        $account = (new CoaAccountMapper)->fromDetail(CoreCoaAccount::detailResponse()['result']);

        $this->assertNotNull($account);
        $this->assertSame('1101', $account->coaNo);
        $this->assertCount(1, $account->mappings);
        $this->assertSame('CASH', $account->mappings[0]->key);
        $this->assertSame(2, $account->mappings[0]->typeId);
        $this->assertSame('admin', $account->createdBy);
    }

    #[Test]
    public function a_detail_without_a_number_is_no_account(): void
    {
        $this->assertNull((new CoaAccountMapper)->fromDetail(['coaNo' => '']));
    }
}
