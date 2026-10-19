<?php

namespace Tests\Unit\Integrations\ESB;

use App\Integrations\ESB\Mappers\SimplePurchaseMapper;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreSimplePurchase;
use Tests\TestCase;

/**
 * Maps rows from GET /purchase/simple-purchase and the result of
 * GET /purchase/simple-purchase/{cashPurchaseNum} onto the canonical shape.
 *
 * Fields are transcribed from the vendor's OpenAPI description. Its examples
 * are zero-valued placeholders, so date formats are not documented and are
 * carried as the ESB sent them.
 */
class SimplePurchaseMapperTest extends TestCase
{
    private function mapper(): SimplePurchaseMapper
    {
        return new SimplePurchaseMapper;
    }

    #[Test]
    public function it_maps_a_list_row(): void
    {
        $summary = $this->mapper()->summaryFromRow(CoreSimplePurchase::row());

        $this->assertSame('esb', $summary->provider);
        $this->assertSame('CP202603200001', $summary->number);
        $this->assertSame('NOTA-0331', $summary->supplierInvoiceNumber);
        $this->assertSame('2026-03-20', $summary->date);
        $this->assertSame(3, $summary->branchId);
        $this->assertSame('Outlet Senopati', $summary->branchName);
        $this->assertSame(14, $summary->supplierId);
        $this->assertSame('Pasar Santa', $summary->supplierName);
        $this->assertSame('Rp', $summary->currencySign);
        $this->assertSame(255000.5, $summary->total);
        $this->assertSame(1, $summary->statusId);
        $this->assertSame('New', $summary->statusName);
        $this->assertSame('andi', $summary->createdByName);
        $this->assertSame('Market run', $summary->notes);
    }

    #[Test]
    public function a_placeholder_row_maps_to_nulls_not_to_invented_values(): void
    {
        $summary = $this->mapper()->summaryFromRow([
            'branchID' => 0,
            'branchName' => '',
            'supplierID' => 0,
            'supplierName' => '',
            'supplierInvoiceNum' => '',
            'currencySign' => '',
            'cashPurchaseTotal' => 0,
            'cashPurchaseDate' => '',
            'cashPurchaseNum' => '',
            'statusID' => 0,
            'statusName' => '',
        ]);

        $this->assertSame('', $summary->number);
        $this->assertNull($summary->branchId);
        $this->assertNull($summary->supplierId);
        $this->assertNull($summary->supplierInvoiceNumber);
        $this->assertNull($summary->currencySign);
        $this->assertNull($summary->date);
        // A real total of zero is possible, so it is kept.
        $this->assertSame(0.0, $summary->total);
    }

    #[Test]
    public function values_of_the_wrong_type_are_dropped(): void
    {
        $summary = $this->mapper()->summaryFromRow([
            'cashPurchaseNum' => ['not', 'a', 'string'],
            'branchID' => 'three',
            'cashPurchaseTotal' => 'lots',
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
        $purchase = $this->mapper()->fromDetail(CoreSimplePurchase::detail());

        $this->assertSame('CP202603200001', $purchase->number);
        $this->assertSame('NOTA-0331', $purchase->supplierInvoiceNumber);
        $this->assertSame('2026-03-20', $purchase->date);
        $this->assertSame('2026-03-20', $purchase->dueDate);
        $this->assertSame('Regular', $purchase->purchaseTypeName);
        $this->assertSame('Outlet Senopati', $purchase->branchName);
        $this->assertSame('Main store', $purchase->locationName);
        $this->assertSame(14, $purchase->supplierId);
        $this->assertSame('Pasar Santa', $purchase->supplierName);
        $this->assertSame('021-7200000', $purchase->supplierPhone);
        $this->assertSame('Bu Sari', $purchase->contactName);
        $this->assertSame('Cash', $purchase->paymentMethodName);
        $this->assertSame('Rupiah', $purchase->currencyName);
        $this->assertSame('Rp', $purchase->currencySign);
        $this->assertSame(1.0, $purchase->rate);
        $this->assertSame('Kitchen', $purchase->costCenterName);
        $this->assertNull($purchase->projectName);
        $this->assertSame(15000.0, $purchase->costTotal);
        $this->assertSame(255000.0, $purchase->total);
        $this->assertSame(1, $purchase->statusId);
        $this->assertSame('New', $purchase->statusName);
        $this->assertSame('Market run', $purchase->notes);
        $this->assertSame('Andi Wijaya', $purchase->createdByName);
        $this->assertSame('2026-03-20 09:15:00', $purchase->createdAt);
        $this->assertNull($purchase->editedByName);
        $this->assertNull($purchase->authorizedByName);
    }

    #[Test]
    public function the_authorizer_falls_back_to_the_username_field(): void
    {
        $purchase = $this->mapper()->fromDetail(CoreSimplePurchase::detail([
            'authorizer' => null,
            'authorizedBy' => 'rina',
            'authorizedDate' => '2026-03-21 08:00:00',
        ]));

        $this->assertSame('rina', $purchase->authorizedByName);
        $this->assertSame('2026-03-21 08:00:00', $purchase->authorizedAt);
    }

    #[Test]
    public function it_maps_the_lines_and_skips_malformed_ones(): void
    {
        $detail = CoreSimplePurchase::detail();
        $detail['simplePurchaseDetails'][] = 'garbage';
        $detail['simplePurchaseDetails'][] = null;

        $purchase = $this->mapper()->fromDetail($detail);

        $this->assertCount(1, $purchase->lines);
        $line = $purchase->lines[0];

        $this->assertSame(702, $line->productDetailId);
        $this->assertSame('Cabai_merah', $line->productCode);
        $this->assertSame('Cabai merah', $line->productName);
        $this->assertSame(20.0, $line->qty);
        $this->assertSame('Kg', $line->uomName);
        $this->assertSame(12000.0, $line->price);
        $this->assertSame(1000.0, $line->discount);
        $this->assertSame(2.5, $line->discountPercent);
        $this->assertSame('PPN 11%', $line->taxName);
        $this->assertSame(239000.0, $line->total);
        $this->assertSame('Fresh', $line->notes);
    }

    #[Test]
    public function it_maps_the_costs_and_skips_ones_without_an_account(): void
    {
        $purchase = $this->mapper()->fromDetail(CoreSimplePurchase::detail([
            'simplePurchaseCosts' => [
                ['amount' => 15000, 'coaNo' => '6100', 'description' => 'Parking', 'notes' => 'Market parking'],
                ['amount' => 0, 'coaNo' => '', 'description' => '', 'notes' => ''],
                'garbage',
            ],
        ]));

        $this->assertCount(1, $purchase->costs);
        $this->assertSame('6100', $purchase->costs[0]->accountNumber);
        $this->assertSame('Parking', $purchase->costs[0]->description);
        $this->assertSame(15000.0, $purchase->costs[0]->amount);
        $this->assertSame('Market parking', $purchase->costs[0]->notes);
    }

    #[Test]
    public function it_maps_the_payments_and_skips_placeholders(): void
    {
        $detail = CoreSimplePurchase::detail();
        $detail['purchasePayments'][] = ['purchasePaymentNum' => '', 'paymentTotal' => 0];

        $purchase = $this->mapper()->fromDetail($detail);

        $this->assertCount(1, $purchase->payments);
        $payment = $purchase->payments[0];

        $this->assertSame('PP202603200001', $payment->number);
        $this->assertSame('2026-03-20', $payment->date);
        $this->assertSame(255000.0, $payment->amount);
        $this->assertSame('Petty cash', $payment->accountName);
        $this->assertSame('Authorized', $payment->statusName);
    }

    #[Test]
    public function vendor_urls_and_supplier_contact_details_do_not_survive_the_mapping(): void
    {
        $json = (string) json_encode($this->mapper()->fromDetail(CoreSimplePurchase::detail()));

        $this->assertStringNotContainsString('esb.co.id', $json);
        $this->assertStringNotContainsString('sari@pasar.example', $json);
        $this->assertStringNotContainsString('0812000000', $json);
    }

    #[Test]
    public function a_detail_with_no_lines_maps_to_empty_lists(): void
    {
        $purchase = $this->mapper()->fromDetail(['cashPurchaseNum' => 'CP1', 'simplePurchaseDetails' => 'x']);

        $this->assertSame([], $purchase->lines);
        $this->assertSame([], $purchase->costs);
        $this->assertSame([], $purchase->payments);
    }
}
