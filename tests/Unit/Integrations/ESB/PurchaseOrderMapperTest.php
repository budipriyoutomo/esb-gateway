<?php

namespace Tests\Unit\Integrations\ESB;

use App\Integrations\ESB\Mappers\PurchaseOrderMapper;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CorePurchaseOrder;
use Tests\TestCase;

/**
 * Maps rows from GET /purchase/purchase-order and the result of
 * GET /purchase/purchase-order/{purchaseNum} onto the canonical shape.
 *
 * Fields are transcribed from the vendor's OpenAPI description. Its examples
 * are zero-valued placeholders, so date formats are not documented and are
 * carried as the ESB sent them.
 */
class PurchaseOrderMapperTest extends TestCase
{
    private function mapper(): PurchaseOrderMapper
    {
        return new PurchaseOrderMapper;
    }

    #[Test]
    public function it_maps_a_list_row(): void
    {
        $summary = $this->mapper()->summaryFromRow(CorePurchaseOrder::row());

        $this->assertSame('esb', $summary->provider);
        $this->assertSame('PO202603200001', $summary->number);
        $this->assertSame('2026-03-20', $summary->date);
        $this->assertSame('2026-03-25', $summary->requiredDate);
        $this->assertSame(3, $summary->branchId);
        $this->assertSame('Outlet Senopati', $summary->branchName);
        $this->assertSame(14, $summary->supplierId);
        $this->assertSame('PT Sumber Air', $summary->supplierName);
        $this->assertSame(1, $summary->currencyId);
        $this->assertSame('Rp', $summary->currencySign);
        $this->assertSame(1110000.5, $summary->total);
        $this->assertSame(1, $summary->statusId);
        $this->assertSame('New', $summary->statusName);
        $this->assertSame('PR202603180002', $summary->purchaseRequestNumbers);
        $this->assertSame('andi', $summary->createdByName);
        $this->assertSame('Weekly water order', $summary->notes);
    }

    #[Test]
    public function a_placeholder_row_maps_to_nulls_not_to_invented_values(): void
    {
        // The vendor's own example: zero ids and empty strings mean "unset".
        $summary = $this->mapper()->summaryFromRow([
            'branchID' => 0,
            'branchName' => '',
            'supplierID' => 0,
            'supplierName' => '',
            'currencyID' => 0,
            'currencySign' => '',
            'purchaseTotal' => 0,
            'purchaseDate' => '',
            'purchaseNum' => '',
            'purchaseRequestNums' => '',
            'statusID' => 0,
            'statusName' => '',
        ]);

        $this->assertSame('', $summary->number);
        $this->assertNull($summary->branchId);
        $this->assertNull($summary->supplierId);
        $this->assertNull($summary->currencyId);
        $this->assertNull($summary->currencySign);
        $this->assertNull($summary->date);
        $this->assertNull($summary->purchaseRequestNumbers);
        // A real total of zero is possible, so it is kept.
        $this->assertSame(0.0, $summary->total);
    }

    #[Test]
    public function values_of_the_wrong_type_are_dropped(): void
    {
        $summary = $this->mapper()->summaryFromRow([
            'purchaseNum' => ['not', 'a', 'string'],
            'branchID' => 'three',
            'purchaseTotal' => 'lots',
            'statusName' => 42,
        ]);

        $this->assertSame('', $summary->number);
        $this->assertNull($summary->branchId);
        $this->assertNull($summary->total);
        $this->assertNull($summary->statusName);
    }

    #[Test]
    public function it_maps_the_detail_header(): void
    {
        $order = $this->mapper()->fromDetail(CorePurchaseOrder::detail());

        $this->assertSame('PO202603200001', $order->number);
        $this->assertSame('2026-03-20', $order->date);
        $this->assertSame('2026-03-25', $order->requiredDate);
        $this->assertSame('Regular', $order->purchaseTypeName);
        $this->assertSame('Outlet Senopati', $order->branchName);
        $this->assertSame(14, $order->supplierId);
        $this->assertSame('PT Sumber Air', $order->supplierName);
        $this->assertSame('Jl. Industri 4, Bekasi', $order->supplierAddress);
        $this->assertSame('021-8800000', $order->supplierPhone);
        $this->assertNull($order->supplierBranchName);
        $this->assertSame('Budi', $order->contactName);
        $this->assertSame('Rupiah', $order->currencyName);
        $this->assertSame('Rp', $order->currencySign);
        $this->assertSame(1.0, $order->rate);
        $this->assertSame(30, $order->dueDays);
        $this->assertSame('Bar', $order->costCenterName);
        $this->assertNull($order->projectName);
        $this->assertSame(1110000.0, $order->total);
        $this->assertSame(1, $order->statusId);
        $this->assertSame('New', $order->statusName);
        $this->assertSame('Weekly water order', $order->notes);
        $this->assertSame('Deliver before 10:00', $order->footNote);
        $this->assertSame('PR202603180002', $order->purchaseRequestNumbers);
        $this->assertSame('Andi Wijaya', $order->createdByName);
        $this->assertSame('2026-03-20 09:15:00', $order->createdAt);
        $this->assertNull($order->editedByName);
        $this->assertNull($order->authorizedByName);
        $this->assertNull($order->authorizedAt);
    }

    #[Test]
    public function the_erps_access_flags_are_carried_as_it_sent_them(): void
    {
        $order = $this->mapper()->fromDetail(CorePurchaseOrder::detail());

        $this->assertTrue($order->canEdit);
        $this->assertFalse($order->canDelete);
    }

    #[Test]
    public function missing_access_flags_are_unknown_not_false(): void
    {
        // Unknown must not hide an action the ERP might accept; the ERP is
        // left to refuse it.
        $order = $this->mapper()->fromDetail(CorePurchaseOrder::detail(['access' => null]));

        $this->assertNull($order->canEdit);
        $this->assertNull($order->canDelete);
    }

    #[Test]
    public function the_authorizer_falls_back_to_the_username_field(): void
    {
        $order = $this->mapper()->fromDetail(CorePurchaseOrder::detail([
            'authorizer' => null,
            'authorizedBy' => 'rina',
            'authorizedDate' => '2026-03-21 08:00:00',
        ]));

        $this->assertSame('rina', $order->authorizedByName);
        $this->assertSame('2026-03-21 08:00:00', $order->authorizedAt);
    }

    #[Test]
    public function it_maps_the_lines_and_skips_malformed_ones(): void
    {
        $detail = CorePurchaseOrder::detail();
        $detail['purchaseDetails'][] = 'garbage';
        $detail['purchaseDetails'][] = null;

        $order = $this->mapper()->fromDetail($detail);

        $this->assertCount(1, $order->lines);
        $line = $order->lines[0];

        $this->assertSame(901, $line->productDetailId);
        $this->assertSame('Air_mineral', $line->productCode);
        $this->assertSame('Air mineral', $line->productName);
        $this->assertSame(200.0, $line->qty);
        $this->assertSame(180.0, $line->requestedQty);
        $this->assertSame('Bottle', $line->uomName);
        $this->assertSame(5000.0, $line->price);
        $this->assertSame(1000.0, $line->discount);
        $this->assertSame(2.5, $line->discountPercent);
        $this->assertSame(999000.0, $line->total);
        $this->assertSame('Cold storage', $line->notes);
    }

    #[Test]
    public function it_maps_the_linked_goods_receipts_and_invoices(): void
    {
        $order = $this->mapper()->fromDetail(CorePurchaseOrder::detail());

        $this->assertCount(1, $order->goodsReceipts);
        $this->assertSame('GR202603250002', $order->goodsReceipts[0]->number);
        $this->assertSame('2026-03-25', $order->goodsReceipts[0]->date);
        $this->assertSame('Authorized', $order->goodsReceipts[0]->statusName);
        $this->assertNull($order->goodsReceipts[0]->total);

        $this->assertCount(1, $order->purchaseInvoices);
        $this->assertSame('PI202603260005', $order->purchaseInvoices[0]->number);
        $this->assertSame('2026-03-26', $order->purchaseInvoices[0]->date);
        $this->assertSame('New', $order->purchaseInvoices[0]->statusName);
        $this->assertSame(1110000.0, $order->purchaseInvoices[0]->total);
    }

    #[Test]
    public function a_placeholder_linked_document_without_a_number_is_skipped(): void
    {
        // The vendor's example lists one zero-valued goods receipt.
        $order = $this->mapper()->fromDetail(CorePurchaseOrder::detail([
            'goodsReceiptHead' => [['goodsReceiptDate' => '', 'goodsReceiptNum' => '', 'statusID' => 0, 'statusName' => '']],
            'purchaseInvoiceHead' => 'x',
        ]));

        $this->assertSame([], $order->goodsReceipts);
        $this->assertSame([], $order->purchaseInvoices);
    }

    #[Test]
    public function vendor_urls_and_supplier_contact_details_do_not_survive_the_mapping(): void
    {
        $json = (string) json_encode($this->mapper()->fromDetail(CorePurchaseOrder::detail()));

        $this->assertStringNotContainsString('esb.co.id', $json);
        // The PIC's personal email and mobile number are not needed by any
        // screen, so they are not carried.
        $this->assertStringNotContainsString('budi@sumber-air.example', $json);
        $this->assertStringNotContainsString('0812000000', $json);
    }

    #[Test]
    public function a_detail_with_no_lines_maps_to_an_empty_list(): void
    {
        $order = $this->mapper()->fromDetail(['purchaseNum' => 'PO1', 'purchaseDetails' => 'x']);

        $this->assertSame([], $order->lines);
        $this->assertSame([], $order->goodsReceipts);
        $this->assertSame([], $order->purchaseInvoices);
    }
}
