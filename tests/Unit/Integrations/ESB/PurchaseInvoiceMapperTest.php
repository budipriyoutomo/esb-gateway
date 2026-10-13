<?php

namespace Tests\Unit\Integrations\ESB;

use App\Integrations\ESB\Mappers\PurchaseInvoiceMapper;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Maps rows from GET /purchase/purchase-invoices and the result of
 * GET /purchase/purchase-invoices/{purchaseInvoiceNum} onto the canonical shape.
 *
 * Fields are transcribed from the vendor's OpenAPI description. Its examples
 * are zero-valued placeholders, so date formats and status ids are not
 * documented and are carried as the ESB sent them.
 */
class PurchaseInvoiceMapperTest extends TestCase
{
    private function mapper(): PurchaseInvoiceMapper
    {
        return new PurchaseInvoiceMapper;
    }

    #[Test]
    public function it_maps_a_list_row(): void
    {
        $summary = $this->mapper()->summaryFromRow([
            'additionalInfo' => 'March supplies',
            'branchID' => 3,
            'branchName' => 'Outlet Senopati',
            'createdBy' => 'andi',
            'createdByFullName' => 'Andi Wijaya',
            'currencyID' => 1,
            'currencySign' => 'Rp',
            'invoiceTotal' => 1250000.5,
            'purchaseInvoiceDate' => '2026-03-26',
            'purchaseInvoiceDueDate' => '2026-04-25',
            'purchaseInvoiceNum' => 'PI202603260005',
            'statusID' => 1,
            'statusName' => 'Open',
            'supplierID' => 14,
            'supplierInvoiceNum' => 'INV/SUP/0331',
            'supplierName' => 'PT Sumber Air',
        ]);

        $this->assertSame('esb', $summary->provider);
        $this->assertSame('PI202603260005', $summary->number);
        $this->assertSame('INV/SUP/0331', $summary->supplierInvoiceNumber);
        $this->assertSame('2026-03-26', $summary->date);
        $this->assertSame('2026-04-25', $summary->dueDate);
        $this->assertSame(3, $summary->branchId);
        $this->assertSame(14, $summary->supplierId);
        $this->assertSame('PT Sumber Air', $summary->supplierName);
        $this->assertSame(1, $summary->currencyId);
        $this->assertSame('Rp', $summary->currencySign);
        $this->assertSame(1250000.5, $summary->total);
        $this->assertSame(1, $summary->statusId);
        $this->assertSame('Open', $summary->statusName);
        $this->assertSame('Andi Wijaya', $summary->createdByName);
        $this->assertSame('March supplies', $summary->notes);
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
            'invoiceTotal' => 0,
            'purchaseInvoiceDate' => '',
            'purchaseInvoiceNum' => '',
            'statusID' => 0,
            'statusName' => '',
        ]);

        $this->assertSame('', $summary->number);
        $this->assertNull($summary->branchId);
        $this->assertNull($summary->supplierId);
        $this->assertNull($summary->currencyId);
        $this->assertNull($summary->currencySign);
        $this->assertNull($summary->date);
        // A real total of zero is possible, so it is kept.
        $this->assertSame(0.0, $summary->total);
    }

    #[Test]
    public function the_list_falls_back_to_the_username_when_there_is_no_full_name(): void
    {
        $summary = $this->mapper()->summaryFromRow(['createdBy' => 'andi', 'createdByFullName' => '']);

        $this->assertSame('andi', $summary->createdByName);
    }

    #[Test]
    public function values_of_the_wrong_type_are_dropped(): void
    {
        $summary = $this->mapper()->summaryFromRow([
            'purchaseInvoiceNum' => ['not', 'a', 'string'],
            'branchID' => 'three',
            'invoiceTotal' => 'lots',
            'statusName' => 42,
        ]);

        $this->assertSame('', $summary->number);
        $this->assertNull($summary->branchId);
        $this->assertNull($summary->total);
        $this->assertNull($summary->statusName);
    }

    /**
     * @return array<string, mixed>
     */
    private function detailResult(): array
    {
        return [
            'additionalInfo' => 'March supplies',
            'authorizedDate' => '2026-03-27 08:00:00',
            'authorizer' => ['fullName' => 'Rina', 'username' => 'rina'],
            'branchID' => 3,
            'branchName' => 'Outlet Senopati',
            'costTotal' => 50000,
            'costs' => [
                ['ID' => 1, 'amount' => 50000, 'coaDescription' => 'Freight', 'coaNo' => '6100'],
                'garbage',
            ],
            'createdDate' => '2026-03-26 09:15:00',
            'creator' => ['fullName' => 'Andi Wijaya', 'username' => 'andi'],
            'currencyID' => 1,
            'currencyName' => 'Rupiah',
            'currencySign' => 'Rp',
            'details' => [
                [
                    'costCenterID' => 5,
                    'costCenterName' => 'Bar',
                    'discount' => 1000,
                    'discountPercent' => 2.5,
                    'price' => 5000,
                    'productCode' => 'Air_mineral',
                    'productDetailID' => 901,
                    'productName' => 'Air mineral',
                    'projectID' => 0,
                    'projectName' => '',
                    'purchaseNumber' => 'PO202603200001',
                    'qty' => 200,
                    'refNum' => 'GR202603250002',
                    'taxName' => 'PPN 11%',
                    'total' => 999000,
                    'transDate' => '2026-03-25',
                    'uomName' => 'Bottle',
                ],
                null,
            ],
            'dueDay' => 30,
            'editedDate' => '',
            'editor' => ['fullName' => '', 'username' => ''],
            'imageAttachments' => ['https://storage.esb.co.id/attachments/pi/1.jpg'],
            'invoiceTotal' => 1160000,
            'printData' => ['companyLogo' => 'https://storage.esb.co.id/logo.png', 'statusName' => 'Authorized'],
            'purchaseInvoiceDate' => '2026-03-26',
            'purchaseInvoiceDueDate' => '2026-04-25',
            'purchaseInvoiceNum' => 'PI202603260005',
            'rate' => 1,
            'refDate' => '2026-03-25',
            'referenceTotal' => 1110000,
            'statusID' => 2,
            'supplierID' => 14,
            'supplierInvoiceNum' => 'INV/SUP/0331',
            'supplierName' => 'PT Sumber Air',
            'vatInvoiceNum' => '010.000-26.00000001',
        ];
    }

    #[Test]
    public function it_maps_the_detail_header(): void
    {
        $invoice = $this->mapper()->fromDetail($this->detailResult());

        $this->assertSame('PI202603260005', $invoice->number);
        $this->assertSame('INV/SUP/0331', $invoice->supplierInvoiceNumber);
        $this->assertSame('010.000-26.00000001', $invoice->taxInvoiceNumber);
        $this->assertSame('2026-03-26', $invoice->date);
        $this->assertSame('2026-04-25', $invoice->dueDate);
        $this->assertSame(30, $invoice->dueDays);
        $this->assertSame('2026-03-25', $invoice->referenceDate);
        $this->assertSame('Rupiah', $invoice->currencyName);
        $this->assertSame(1.0, $invoice->rate);
        $this->assertSame(1110000.0, $invoice->referenceTotal);
        $this->assertSame(50000.0, $invoice->costTotal);
        $this->assertSame(1160000.0, $invoice->total);
        $this->assertSame(2, $invoice->statusId);
        $this->assertSame('Andi Wijaya', $invoice->createdByName);
        $this->assertSame('2026-03-26 09:15:00', $invoice->createdAt);
        $this->assertNull($invoice->editedByName);
        $this->assertNull($invoice->editedAt);
        $this->assertSame('Rina', $invoice->authorizedByName);
    }

    #[Test]
    public function the_detail_status_name_is_read_from_print_data(): void
    {
        // The detail result documents only statusID at the top level; the
        // label appears under printData.
        $invoice = $this->mapper()->fromDetail($this->detailResult());

        $this->assertSame('Authorized', $invoice->statusName);
    }

    #[Test]
    public function a_top_level_status_name_wins_if_the_esb_ever_sends_one(): void
    {
        $invoice = $this->mapper()->fromDetail([...$this->detailResult(), 'statusName' => 'Open']);

        $this->assertSame('Open', $invoice->statusName);
    }

    #[Test]
    public function it_maps_the_lines_and_skips_malformed_ones(): void
    {
        $invoice = $this->mapper()->fromDetail($this->detailResult());

        $this->assertCount(1, $invoice->lines);
        $line = $invoice->lines[0];

        $this->assertSame(901, $line->productDetailId);
        $this->assertSame('Air_mineral', $line->productCode);
        $this->assertSame('Air mineral', $line->productName);
        $this->assertSame('GR202603250002', $line->referenceNumber);
        $this->assertSame('2026-03-25', $line->referenceDate);
        $this->assertSame('PO202603200001', $line->purchaseOrderNumber);
        $this->assertSame(200.0, $line->qty);
        $this->assertSame('Bottle', $line->uomName);
        $this->assertSame(5000.0, $line->price);
        $this->assertSame(1000.0, $line->discount);
        $this->assertSame(2.5, $line->discountPercent);
        $this->assertSame('PPN 11%', $line->taxName);
        $this->assertSame(999000.0, $line->total);
        $this->assertSame('Bar', $line->costCenterName);
        $this->assertNull($line->projectName);
    }

    #[Test]
    public function it_maps_the_costs_and_skips_malformed_ones(): void
    {
        $invoice = $this->mapper()->fromDetail($this->detailResult());

        $this->assertCount(1, $invoice->costs);
        $this->assertSame('6100', $invoice->costs[0]->accountNumber);
        $this->assertSame('Freight', $invoice->costs[0]->accountName);
        $this->assertSame(50000.0, $invoice->costs[0]->amount);
    }

    #[Test]
    public function vendor_urls_do_not_survive_the_mapping(): void
    {
        $invoice = $this->mapper()->fromDetail($this->detailResult());

        $this->assertStringNotContainsString('esb.co.id', (string) json_encode($invoice));
    }

    #[Test]
    public function a_detail_with_no_lines_or_costs_maps_to_empty_lists(): void
    {
        $invoice = $this->mapper()->fromDetail(['purchaseInvoiceNum' => 'PI1', 'details' => 'x', 'costs' => null]);

        $this->assertSame([], $invoice->lines);
        $this->assertSame([], $invoice->costs);
    }
}
