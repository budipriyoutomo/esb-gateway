<?php

namespace Tests\Unit\Integrations\ESB;

use App\Domain\Canonical\InventoryCarryOver;
use App\Domain\Canonical\InventoryKind;
use App\Domain\Canonical\InventoryParty;
use App\Integrations\ESB\Mappers\InventoryDocumentMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\CoreInventory;

/**
 * The five inventory documents onto one canonical shape ([ESB-033], ADR-026).
 */
class InventoryDocumentMapperTest extends TestCase
{
    private function mapper(): InventoryDocumentMapper
    {
        return new InventoryDocumentMapper;
    }

    #[Test]
    public function a_transfer_request_row_goes_from_one_branch_to_another(): void
    {
        $row = $this->mapper()->summaryFromRow(InventoryKind::GoodsTransferRequests, CoreInventory::gtrRow());

        $this->assertNotNull($row);
        $this->assertSame('goods-transfer-requests', $row->kind);
        $this->assertSame(CoreInventory::GTR, $row->number);
        $this->assertSame('2026-03-20', $row->date);
        $this->assertSame(3, $row->statusId);
        $this->assertSame('Authorized', $row->statusName);
        $this->assertEquals(new InventoryParty(branchName: 'Gudang Pusat'), $row->from);
        $this->assertEquals(new InventoryParty(branchName: 'Outlet Senopati'), $row->to);
        $this->assertSame('PR202603190001', $row->reference);
        $this->assertSame('Restock outlet', $row->notes);
    }

    #[Test]
    public function a_transfer_request_detail_has_its_lines_and_linked_purchase_requests(): void
    {
        $doc = $this->mapper()->fromDetail(InventoryKind::GoodsTransferRequests, CoreInventory::gtrDetail()['result']);

        $this->assertNotNull($doc);
        $this->assertEquals(new InventoryParty(branchId: 1, branchName: 'Gudang Pusat', locationId: 100, locationName: 'Gudang Utama'), $doc->from);
        $this->assertEquals(new InventoryParty(branchId: 3, branchName: 'Outlet Senopati'), $doc->to);
        $this->assertSame('Purchase request', $doc->linkedDocuments[0]->type);
        $this->assertSame('PR202603190001', $doc->linkedDocuments[0]->number);
        $this->assertSame(1, $doc->attachmentCount);
        $this->assertSame('andi', $doc->createdBy);
        $this->assertSame('2026-03-20 08:30:00', $doc->createdAt);
        $this->assertSame('budi', $doc->authorizedBy);
        $this->assertNull($doc->editedBy);

        $line = $doc->lines[0];
        $this->assertSame(501, $line->productDetailId);
        $this->assertSame('BB-001', $line->productCode);
        $this->assertSame('Beras Premium', $line->productName);
        $this->assertSame('KG', $line->unit);
        $this->assertSame(20.0, $line->qty);
        $this->assertSame(25.0, $line->requestedQty);
        $this->assertSame(80.0, $line->availableQty);
        $this->assertSame(85.0, $line->stockQty);
        $this->assertSame(5.0, $line->pendingQty);
    }

    #[Test]
    public function a_delivery_goes_to_a_branch_or_a_customer(): void
    {
        $toBranch = $this->mapper()->summaryFromRow(InventoryKind::GoodsDeliveries, CoreInventory::gdRow());
        $toCustomer = $this->mapper()->summaryFromRow(InventoryKind::GoodsDeliveries, CoreInventory::gdRow([
            'destBranchName' => null, 'destinationBranchID' => 0, 'customerName' => 'PT Pelanggan',
        ]));

        $this->assertEquals(new InventoryParty(branchId: 1, branchName: 'Gudang Pusat'), $toBranch?->from);
        $this->assertEquals(new InventoryParty(branchId: 3, branchName: 'Outlet Senopati'), $toBranch?->to);
        $this->assertSame('Transfer Request', $toBranch?->transactionType);
        $this->assertSame(CoreInventory::GTR, $toBranch?->reference);
        $this->assertEquals(new InventoryParty(name: 'PT Pelanggan'), $toCustomer?->to);
    }

    #[Test]
    public function a_delivery_detail_reads_delivered_quantities_and_cost(): void
    {
        $doc = $this->mapper()->fromDetail(InventoryKind::GoodsDeliveries, CoreInventory::gdDetail()['result']);

        $this->assertNotNull($doc);
        $this->assertEquals(new InventoryParty(branchId: 1, branchName: 'Gudang Pusat', locationId: 100, locationName: 'Gudang Utama', address: 'Jl. Industri 1'), $doc->from);
        $this->assertSame('2026-03-20', $doc->referenceDate);
        $this->assertSame('2026-03-21 09:00:00', $doc->createdAt);
        $this->assertSame('2026-03-21 11:00:00', $doc->authorizedAt);
        $this->assertSame(0, $doc->attachmentCount);

        $line = $doc->lines[0];
        $this->assertSame(20.0, $line->qty);
        $this->assertSame(25.0, $line->requestedQty);
        // 0 received is a real quantity on a delivery, not "none".
        $this->assertSame(0.0, $line->receivedQty);
        $this->assertSame(12000.5, $line->cost);
        $this->assertSame('Fragile', $line->notes);
        $this->assertNull($line->outstandingQty);
    }

    #[Test]
    public function outstanding_quantities_are_merged_onto_the_lines_by_product_detail(): void
    {
        $doc = $this->mapper()->fromDetail(InventoryKind::GoodsDeliveries, CoreInventory::gdDetail()['result']);
        $this->assertNotNull($doc);

        $merged = $doc->withOutstanding($this->mapper()->outstandingFrom(CoreInventory::gdOutstanding()['result']));

        $this->assertSame(5.0, $merged->lines[0]->outstandingQty);
    }

    #[Test]
    public function a_receipt_comes_from_its_source_into_a_branch_and_lists_its_invoices(): void
    {
        $row = $this->mapper()->summaryFromRow(InventoryKind::GoodsReceipts, CoreInventory::grRow());
        $doc = $this->mapper()->fromDetail(InventoryKind::GoodsReceipts, CoreInventory::grDetail()['result']);

        $this->assertEquals(new InventoryParty(name: 'CV Maju Jaya'), $row?->from);
        $this->assertEquals(new InventoryParty(branchId: 3, branchName: 'Outlet Senopati', locationId: 300), $row?->to);
        $this->assertSame('sari', $row?->createdBy);

        $this->assertNotNull($doc);
        $this->assertEquals(new InventoryParty(name: 'CV Maju Jaya'), $doc->from);
        $this->assertEquals(new InventoryParty(branchId: 3, branchName: 'Outlet Senopati', locationId: 300, locationName: 'Gudang Outlet'), $doc->to);
        $this->assertSame('PO202603200001', $doc->reference);
        $this->assertSame('Purchase invoice', $doc->linkedDocuments[0]->type);
        $this->assertSame('PI202603230001', $doc->linkedDocuments[0]->number);
        $this->assertSame('New', $doc->linkedDocuments[0]->statusName);
        // The placeholder attachment without a URL is not counted.
        $this->assertSame(1, $doc->attachmentCount);
        $this->assertSame(20.0, $doc->lines[0]->qty);
    }

    #[Test]
    public function an_item_journal_has_one_side_and_lines_with_a_purpose_and_cost(): void
    {
        $doc = $this->mapper()->fromDetail(InventoryKind::ItemJournals, CoreInventory::ijDetail()['result']);

        $this->assertNotNull($doc);
        $this->assertEquals(new InventoryParty(branchId: 3, branchName: 'Outlet Senopati', locationId: 300, locationName: 'Gudang Outlet'), $doc->from);
        $this->assertNull($doc->to);
        $this->assertSame(38, $doc->statusId);
        // An adjustment can take stock out.
        $this->assertSame(-3.0, $doc->lines[0]->qty);
        $this->assertSame('Waste', $doc->lines[0]->purpose);
        $this->assertSame(12000.0, $doc->lines[0]->cost);
    }

    #[Test]
    public function a_simple_transfer_moves_between_locations(): void
    {
        $row = $this->mapper()->summaryFromRow(InventoryKind::SimpleTransfers, CoreInventory::stRow());
        $doc = $this->mapper()->fromDetail(InventoryKind::SimpleTransfers, CoreInventory::stDetail()['result']);

        $this->assertEquals(new InventoryParty(branchName: 'Outlet Senopati', locationName: 'Gudang Outlet'), $row?->from);
        $this->assertEquals(new InventoryParty(branchName: 'Outlet Senopati', locationId: 301, locationName: 'Dapur'), $row?->to);
        $this->assertNull($row?->notes);

        $this->assertNotNull($doc);
        $this->assertSame('New', $doc->statusName ?? 'New');
        $this->assertEquals(new InventoryParty(branchId: 3, branchName: 'Outlet Senopati', locationId: 300, locationName: 'Gudang Outlet'), $doc->from);
        $this->assertNull($doc->project);
        $this->assertNull($doc->lines[0]->productCode);
        $this->assertSame(10.0, $doc->lines[0]->qty);
    }

    #[Test]
    public function a_transfer_request_says_whether_it_can_still_be_edited_or_deleted(): void
    {
        $locked = $this->mapper()->fromDetail(InventoryKind::GoodsTransferRequests, CoreInventory::gtrDetail()['result']);
        $open = $this->mapper()->fromDetail(InventoryKind::GoodsTransferRequests, CoreInventory::gtrDetail(['canEdit' => true, 'canDelete' => true])['result']);

        $this->assertFalse($locked?->canEdit);
        $this->assertFalse($locked?->canDelete);
        $this->assertTrue($open?->canEdit);
        $this->assertTrue($open?->canDelete);
    }

    #[Test]
    public function documents_without_the_flags_leave_the_choice_to_the_erp(): void
    {
        $doc = $this->mapper()->fromDetail(InventoryKind::ItemJournals, CoreInventory::ijDetail()['result']);

        $this->assertNull($doc?->canEdit);
        $this->assertNull($doc?->canDelete);
    }

    #[Test]
    public function an_item_journal_line_keeps_its_purpose_id_for_editing(): void
    {
        $doc = $this->mapper()->fromDetail(InventoryKind::ItemJournals, CoreInventory::ijDetail()['result']);

        $this->assertSame(2, $doc?->lines[0]->purposeId);
    }

    #[Test]
    public function an_edit_carries_over_what_the_form_does_not_show(): void
    {
        $gtr = $this->mapper()->fromDetail(InventoryKind::GoodsTransferRequests, CoreInventory::gtrDetail()['result']);
        $ij = $this->mapper()->fromDetail(InventoryKind::ItemJournals, CoreInventory::ijDetail(['requestTemplateID' => 12])['result']);
        $st = $this->mapper()->fromDetail(InventoryKind::SimpleTransfers, CoreInventory::stDetail(['costCenterID' => 4, 'projectID' => 9])['result']);

        $this->assertEquals(new InventoryCarryOver(purchaseRequestNumbers: ['PR202603190001']), $gtr?->carryOver);
        $this->assertEquals(new InventoryCarryOver(requestTemplateId: 12), $ij?->carryOver);
        $this->assertEquals(new InventoryCarryOver(costCenterId: 4, projectId: 9), $st?->carryOver);
    }

    #[Test]
    public function a_document_with_assets_says_so(): void
    {
        $assetRequest = $this->mapper()->fromDetail(InventoryKind::GoodsTransferRequests, CoreInventory::gtrDetail(['categoryTypeID' => 3])['result']);
        $withAssets = $this->mapper()->fromDetail(InventoryKind::GoodsTransferRequests, CoreInventory::gtrDetail(['transferDetailAssets' => ['AST-1']])['result']);
        $assetLine = $this->mapper()->fromDetail(InventoryKind::SimpleTransfers, CoreInventory::stDetail([
            'simpleTransferDetails' => [[...CoreInventory::stDetail()['result']['simpleTransferDetails'][0], 'isAsset' => true, 'assetIDs' => ['AST-2']]],
        ])['result']);
        $plain = $this->mapper()->fromDetail(InventoryKind::SimpleTransfers, CoreInventory::stDetail()['result']);

        $this->assertTrue($assetRequest?->carryOver->hasAssets);
        $this->assertTrue($withAssets?->carryOver->hasAssets);
        $this->assertTrue($assetLine?->carryOver->hasAssets);
        $this->assertFalse($plain?->carryOver->hasAssets);
    }

    #[Test]
    public function pending_rows_are_kept_without_a_number_by_their_reference(): void
    {
        $delivery = $this->mapper()->summaryFromRow(InventoryKind::GoodsDeliveries, CoreInventory::gdRow([
            'goodsDeliveryNum' => null, 'statusID' => 33, 'statusName' => 'Pending',
        ]));
        $receipt = $this->mapper()->summaryFromRow(InventoryKind::GoodsReceipts, CoreInventory::grRow([
            'goodsReceiptNum' => null, 'statusID' => 33, 'statusName' => 'Pending',
        ]));

        $this->assertNotNull($delivery);
        $this->assertNull($delivery->number);
        $this->assertSame(CoreInventory::GTR, $delivery->reference);
        $this->assertSame(33, $delivery->statusId);
        $this->assertNotNull($receipt);
        $this->assertNull($receipt->number);
        $this->assertSame('PO202603200001', $receipt->reference);
        $this->assertSame('CV Maju Jaya', $receipt->from?->name);
    }

    #[Test]
    public function rows_without_a_number_or_reference_and_details_without_a_number_are_dropped(): void
    {
        $this->assertNull($this->mapper()->summaryFromRow(InventoryKind::GoodsDeliveries, CoreInventory::gdRow(['goodsDeliveryNum' => '', 'referenceNumber' => ''])));
        $this->assertNull($this->mapper()->summaryFromRow(InventoryKind::ItemJournals, CoreInventory::ijRow(['itemJournalNum' => null])));
        $this->assertNull($this->mapper()->fromDetail(InventoryKind::SimpleTransfers, ['simpleTransferNum' => null]));
    }
}
