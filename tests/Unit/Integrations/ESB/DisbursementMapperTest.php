<?php

namespace Tests\Unit\Integrations\ESB;

use App\Integrations\ESB\Mappers\DisbursementMapper;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreDisbursement;
use Tests\TestCase;

/**
 * Maps rows of GET /finance/disbursement and the result of
 * GET /finance/disbursement/{disbursementNum} onto the canonical shape
 * ([ESB-037]). The vendor's examples are zero-valued, so 0 / "" ids and
 * texts are "none"; amounts are kept as sent.
 */
class DisbursementMapperTest extends TestCase
{
    private function mapper(): DisbursementMapper
    {
        return new DisbursementMapper;
    }

    #[Test]
    public function it_maps_a_list_row(): void
    {
        $summary = $this->mapper()->summaryFromRow(CoreDisbursement::row(['overDue' => 1]));

        $this->assertNotNull($summary);
        $this->assertSame('esb', $summary->provider);
        $this->assertSame(CoreDisbursement::NUMBER, $summary->number);
        $this->assertSame('2026-03-25', $summary->date);
        $this->assertSame(3, $summary->branchId);
        $this->assertSame('Outlet Senopati', $summary->branchName);
        $this->assertSame(14, $summary->supplierId);
        $this->assertSame('PT Properti Jaya', $summary->supplierName);
        $this->assertSame('Credit', $summary->paymentMethod);
        $this->assertSame('INV-RUKO-03', $summary->reference);
        $this->assertSame('Sewa ruko Maret', $summary->notes);
        $this->assertSame(15000000.5, $summary->grandTotal);
        $this->assertSame('2026-04-24', $summary->invoiceDueDate);
        $this->assertTrue($summary->overdue);
        $this->assertTrue($summary->hasApproval);
        $this->assertSame(1, $summary->statusId);
        $this->assertSame('New', $summary->statusName);
    }

    #[Test]
    public function a_placeholder_row_maps_to_nulls_and_a_row_without_a_number_is_dropped(): void
    {
        $summary = $this->mapper()->summaryFromRow(CoreDisbursement::row([
            'branchID' => 0,
            'branchName' => '',
            'supplierID' => 0,
            'supplierName' => '',
            'ref' => '',
            'additionalInfo' => null,
            'invoiceDueDate' => '',
            'overDue' => 0,
            'grandTotal' => 'n/a',
            'statusID' => 0,
            'statusName' => '',
        ]));

        $this->assertNotNull($summary);
        $this->assertNull($summary->branchId);
        $this->assertNull($summary->branchName);
        $this->assertNull($summary->supplierId);
        $this->assertNull($summary->reference);
        $this->assertNull($summary->notes);
        $this->assertNull($summary->invoiceDueDate);
        $this->assertFalse($summary->overdue);
        $this->assertNull($summary->grandTotal);
        $this->assertNull($summary->statusId);

        $this->assertSame([], $this->mapper()->summariesFromRows([CoreDisbursement::row(['disbursementNum' => '']), 'not a row']));
    }

    #[Test]
    public function it_maps_the_detail(): void
    {
        $disbursement = $this->mapper()->fromDetail(CoreDisbursement::detailResult([
            'imageAttachments' => ['https://cdn.example.test/a.pdf', ''],
            'authorizedBy' => 'budi',
            'authorizedDate' => '2026-03-26 10:00:00',
        ]));

        $this->assertNotNull($disbursement);
        $this->assertSame(CoreDisbursement::NUMBER, $disbursement->number);
        $this->assertSame('2026-03-25', $disbursement->date);
        $this->assertSame(2, $disbursement->paymentId);
        $this->assertSame('Credit', $disbursement->paymentName);
        $this->assertNull($disbursement->cashBankCoaNo);
        $this->assertSame(14, $disbursement->supplierId);
        $this->assertSame(1, $disbursement->currencyId);
        $this->assertSame('Rupiah', $disbursement->currencyName);
        $this->assertSame('Rp', $disbursement->currencySign);
        $this->assertSame(1.0, $disbursement->rate);
        $this->assertSame(30, $disbursement->creditTerm);
        $this->assertSame('2026-04-24', $disbursement->invoiceDueDate);
        $this->assertSame('INV-RUKO-03', $disbursement->reference);
        $this->assertSame('Sewa ruko Maret', $disbursement->notes);
        $this->assertSame('BCA', $disbursement->bankName);
        $this->assertSame('PT Properti Jaya', $disbursement->bankAccountName);
        $this->assertSame('Operasional', $disbursement->costCenter);
        $this->assertSame('Renovasi', $disbursement->project);
        $this->assertSame(15000000.5, $disbursement->grandTotal);
        $this->assertNull($disbursement->releasePaymentCode);
        $this->assertSame(1, $disbursement->attachmentCount);
        $this->assertSame('andi', $disbursement->createdBy);
        $this->assertSame('2026-03-25 09:00:00', $disbursement->createdAt);
        $this->assertNull($disbursement->editedBy);
        $this->assertSame('budi', $disbursement->authorizedBy);

        $this->assertCount(1, $disbursement->lines);
        $line = $disbursement->lines[0];
        $this->assertSame(3, $line->branchId);
        $this->assertSame('Outlet Senopati', $line->branchName);
        $this->assertSame('6101', $line->coaNo);
        $this->assertSame('Beban Sewa', $line->coaDescription);
        $this->assertSame('Sewa Maret', $line->description);
        $this->assertSame(15000000.5, $line->amount);

        $this->assertCount(1, $disbursement->linkedDocuments);
        $linked = $disbursement->linkedDocuments[0];
        $this->assertSame('Purchase payment', $linked->type);
        $this->assertSame('PP202603280001', $linked->number);
        $this->assertSame('2026-03-28', $linked->date);
        $this->assertSame(5000000.0, $linked->total);
        $this->assertSame('Authorized', $linked->statusName);
    }

    #[Test]
    public function the_whole_bank_account_number_is_shown_and_carried_for_an_update(): void
    {
        $disbursement = $this->mapper()->fromDetail(CoreDisbursement::detailResult());

        // Business decision 2026-10-22: shown in full, not masked.
        $this->assertNotNull($disbursement);
        $this->assertSame('1234567890123', $disbursement->bankAccountNumber);

        // What an edit sends back unchanged ([ESB-039]).
        $this->assertSame(7, $disbursement->carryOver->costCenterId);
        $this->assertSame(4, $disbursement->carryOver->projectId);
        $this->assertSame('BCA', $disbursement->carryOver->bankName);
        $this->assertSame('PT Properti Jaya', $disbursement->carryOver->bankAccountName);
        $this->assertSame('1234567890123', $disbursement->carryOver->bankAccountNumber);

        // The carry-over (cost center / project ids) stays on the server.
        $this->assertArrayNotHasKey('carryOver', $disbursement->jsonSerialize());
    }

    #[Test]
    public function a_cash_disbursement_reads_its_cash_account_and_supplier_settlements(): void
    {
        $disbursement = $this->mapper()->fromDetail(CoreDisbursement::detailResult([
            'paymentID' => 1,
            'paymentName' => 'Cash',
            'disbursementCoaNo' => '1101',
            'creditTerm' => 0,
            'bankAccountNumber' => '',
            'costCenterID' => 0,
            'projectID' => null,
            'purchasePaymentDetails' => null,
            'supplierSettlementDetails' => [
                ['coaNo' => '1101', 'currencyName' => 'Rupiah', 'paymentTotal' => 0, 'payableSettlementDate' => '2026-03-29', 'payableSettlementNum' => 'PS202603290001', 'status' => 'New'],
                ['payableSettlementNum' => ''],
            ],
        ]));

        $this->assertNotNull($disbursement);
        $this->assertSame(1, $disbursement->paymentId);
        $this->assertSame('1101', $disbursement->cashBankCoaNo);
        $this->assertNull($disbursement->creditTerm);
        $this->assertNull($disbursement->bankAccountNumber);
        $this->assertNull($disbursement->carryOver->bankAccountNumber);
        $this->assertNull($disbursement->carryOver->costCenterId);
        $this->assertNull($disbursement->carryOver->projectId);

        $this->assertCount(1, $disbursement->linkedDocuments);
        $this->assertSame('Supplier settlement', $disbursement->linkedDocuments[0]->type);
        $this->assertSame('PS202603290001', $disbursement->linkedDocuments[0]->number);
        $this->assertSame(0.0, $disbursement->linkedDocuments[0]->total);
    }

    #[Test]
    public function a_detail_without_a_number_is_none(): void
    {
        $this->assertNull($this->mapper()->fromDetail(CoreDisbursement::detailResult(['disbursementNum' => ''])));
        $this->assertNull($this->mapper()->fromDetail([]));
    }

    #[Test]
    public function the_import_form_rows_become_draft_lines_and_empty_rows_are_dropped(): void
    {
        $lines = $this->mapper()->formLinesFrom(CoreDisbursement::formLines()['result']);

        $this->assertCount(1, $lines);
        $this->assertSame(3, $lines[0]->branchId);
        $this->assertSame('Outlet Senopati', $lines[0]->branchName);
        $this->assertSame('6102', $lines[0]->coaNo);
        $this->assertSame('Beban Listrik', $lines[0]->coaDescription);
        $this->assertSame('Listrik Maret', $lines[0]->description);
        $this->assertSame(250000.0, $lines[0]->amount);
    }
}
