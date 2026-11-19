<?php

namespace Tests\Unit\Integrations\ESB;

use App\Integrations\ESB\Mappers\BranchMapper;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreBranch;
use Tests\TestCase;

/**
 * ESB Core Branch onto the canonical branch list row and detail ([ESB-051]).
 */
class BranchMapperTest extends TestCase
{
    private function mapper(): BranchMapper
    {
        return new BranchMapper;
    }

    #[Test]
    public function it_maps_list_rows(): void
    {
        $branches = $this->mapper()->summariesFromResult([
            CoreBranch::row(),
            CoreBranch::row(['branchID' => 3, 'branchCode' => '', 'branchName' => 'Gudang Pusat']),
        ]);

        $this->assertCount(2, $branches);
        $this->assertSame('esb', $branches[0]->provider);
        $this->assertSame(12, $branches[0]->id);
        $this->assertSame('OUT1', $branches[0]->code);
        $this->assertSame('Maharasa Kemang', $branches[0]->name);
        $this->assertNull($branches[1]->code);
    }

    #[Test]
    public function list_rows_read_defensively(): void
    {
        $branches = $this->mapper()->summariesFromResult([
            ['branchID' => 0, 'branchCode' => 'X', 'branchName' => 'No id'],
            ['branchID' => 'abc', 'branchName' => 'Bad id'],
            'not a row',
            ['branchID' => 4, 'branchName' => null, 'branchCode' => 9],
        ]);

        // A row without a usable id cannot be opened or matched: dropped.
        $this->assertCount(1, $branches);
        $this->assertSame(4, $branches[0]->id);
        $this->assertSame('', $branches[0]->name);
        $this->assertNull($branches[0]->code);

        $this->assertSame([], $this->mapper()->summariesFromResult(['branchID' => 1]));
    }

    #[Test]
    public function it_maps_the_detail(): void
    {
        $branch = $this->mapper()->fromDetail(CoreBranch::detail());

        $this->assertNotNull($branch);
        $this->assertSame('esb', $branch->provider);
        $this->assertSame(12, $branch->id);
        $this->assertSame('Maharasa Kemang', $branch->name);
        $this->assertSame('OUT1', $branch->code);
        $this->assertSame('KMG', $branch->externalCode);
        $this->assertSame('Kemang', $branch->esbOrderName);
        $this->assertSame(2, $branch->branchTypeId);
        $this->assertNull($branch->brandId);
        $this->assertTrue($branch->active);
        $this->assertSame('Jl. Kemang Raya 10', $branch->address);
        $this->assertSame('021-555-0101', $branch->phone);
        $this->assertSame('12730', $branch->postalCode);
        $this->assertSame('-6.2607', $branch->latitude);
        $this->assertSame('106.8137', $branch->longitude);
        $this->assertSame('2025-03-01', $branch->openDate);
        $this->assertNull($branch->closeDate);
        $this->assertSame('PB1', $branch->taxName);
        $this->assertSame(10, $branch->taxValue);
        $this->assertSame('Service', $branch->additionalTaxName);
        $this->assertSame(5, $branch->additionalTaxValue);
        $this->assertNull($branch->vatName);
        $this->assertSame(1, $branch->timezoneId);
        $this->assertSame('4.2.0', $branch->posVersion);
        $this->assertSame(['cashier' => 2, 'order' => 3, 'tab' => 1, 'serverOnly' => null], $branch->licences);
        $this->assertSame('Maharasa', $branch->printingHeader);
        $this->assertSame('Terima kasih', $branch->printingFooter);
        $this->assertSame('admin', $branch->createdBy);
        $this->assertSame('2026-01-10 08:00:00', $branch->createdAt);
        $this->assertNull($branch->editedBy);
        $this->assertNull($branch->editedAt);
    }

    #[Test]
    public function the_detail_reads_defensively(): void
    {
        $branch = $this->mapper()->fromDetail(CoreBranch::detail([
            'flagActive' => 'maybe',
            'taxValue' => 'ten',
            'additionalTaxValue' => '2.5',
            'cashierCount' => -1,
            'branchName' => null,
        ]));

        $this->assertNotNull($branch);
        $this->assertNull($branch->active);
        $this->assertNull($branch->taxValue);
        $this->assertSame(2.5, $branch->additionalTaxValue);
        $this->assertNull($branch->licences['cashier']);
        $this->assertSame('', $branch->name);
        $this->assertFalse($this->mapper()->fromDetail(CoreBranch::detail(['flagActive' => 0]))?->active);
    }

    #[Test]
    public function image_urls_are_not_carried(): void
    {
        $branch = $this->mapper()->fromDetail(CoreBranch::detail());

        $this->assertNotNull($branch);
        $this->assertStringNotContainsString('cdn.example.test', (string) json_encode($branch));
    }

    #[Test]
    public function a_detail_without_an_id_is_no_branch(): void
    {
        $this->assertNull($this->mapper()->fromDetail([]));
        $this->assertNull($this->mapper()->fromDetail(CoreBranch::detail(['branchID' => 0])));
    }
}
