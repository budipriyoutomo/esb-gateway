<?php

namespace Tests\Unit\Integrations\ESB;

use App\Integrations\ESB\Mappers\PurposeMapper;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CorePurpose;
use Tests\TestCase;

/**
 * ESB Core Purpose onto the canonical Purpose ([ESB-015]).
 */
class PurposeMapperTest extends TestCase
{
    private function mapper(): PurposeMapper
    {
        return new PurposeMapper;
    }

    #[Test]
    public function it_maps_a_list_row(): void
    {
        $purpose = $this->mapper()->summaryFromRow(CorePurpose::row());

        $this->assertSame('esb', $purpose->provider);
        $this->assertSame(7, $purpose->id);
        $this->assertSame('Entertainment', $purpose->name);
        $this->assertSame('6105', $purpose->accountNo);
        $this->assertSame('Biaya Entertainment', $purpose->accountName);
        $this->assertSame(['Stock Usage', 'Petty Cash'], $purpose->appliesTo);
        $this->assertTrue($purpose->active);
    }

    #[Test]
    public function a_list_row_reads_defensively(): void
    {
        $purpose = $this->mapper()->summaryFromRow([
            'purposeID' => 0,
            'purposeName' => null,
            'purposeCoaNo' => '',
            'purposeAppliedTo' => ['', 'Petty Cash', 5, null],
            'flagActive' => 'maybe',
        ]);

        $this->assertNull($purpose->id);
        $this->assertSame('', $purpose->name);
        $this->assertNull($purpose->accountNo);
        $this->assertSame(['Petty Cash'], $purpose->appliesTo);
        $this->assertNull($purpose->active);
        $this->assertFalse($this->mapper()->summaryFromRow(['flagActive' => 0])->active);
        $this->assertSame([], $this->mapper()->summaryFromRow(['purposeAppliedTo' => 'x'])->appliesTo);
    }

    #[Test]
    public function it_maps_the_detail(): void
    {
        $purpose = $this->mapper()->fromDetail(CorePurpose::detail());

        $this->assertNotNull($purpose);
        $this->assertSame('esb', $purpose->provider);
        $this->assertSame(7, $purpose->id);
        $this->assertSame('Entertainment', $purpose->name);
        $this->assertSame('6105', $purpose->accountNo);
        $this->assertTrue($purpose->active);
        $this->assertSame([3, 5], $purpose->transactionTypeIds);
        $this->assertSame('admin', $purpose->createdBy);
        $this->assertSame('2026-01-10 08:00:00', $purpose->createdAt);
        $this->assertNull($purpose->editedBy);
        $this->assertNull($purpose->editedAt);
    }

    #[Test]
    public function a_detail_without_an_id_is_no_purpose(): void
    {
        $this->assertNull($this->mapper()->fromDetail([]));
        $this->assertNull($this->mapper()->fromDetail(CorePurpose::detail(['purposeID' => 0])));
    }
}
