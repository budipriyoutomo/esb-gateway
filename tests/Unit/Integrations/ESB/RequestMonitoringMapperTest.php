<?php

namespace Tests\Unit\Integrations\ESB;

use App\Integrations\ESB\Mappers\RequestMonitoringMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\CoreRequestMonitoring;

/**
 * Request monitoring rows ([ESB-053], ADR-034): PR items not yet ordered.
 */
class RequestMonitoringMapperTest extends TestCase
{
    private function mapper(): RequestMonitoringMapper
    {
        return new RequestMonitoringMapper;
    }

    #[Test]
    public function it_maps_a_row_with_sum_qty_as_the_quantity_not_yet_ordered(): void
    {
        [$item] = $this->mapper()->itemsFromRows([CoreRequestMonitoring::row()]);

        $this->assertSame(CoreRequestMonitoring::PR, $item->purchaseRequestNumber);
        $this->assertSame('2026-03-18', $item->purchaseRequestDate);
        $this->assertSame('2026-03-25', $item->requiredDate);
        $this->assertSame(3, $item->branchId);
        $this->assertSame('Outlet Senopati', $item->branchName);
        $this->assertSame(901, $item->productDetailId);
        $this->assertSame('Air_mineral', $item->productCode);
        $this->assertSame('Air mineral', $item->productName);
        $this->assertSame('Bottle', $item->uomName);
        $this->assertSame(20.0, $item->unorderedQty);
        $this->assertSame(2, $item->requestProcessId);
        $this->assertSame('Purchase', $item->requestProcessName);
        $this->assertSame('PT Sumber Air', $item->supplierName);
    }

    #[Test]
    public function only_the_transfer_process_is_never_ordered(): void
    {
        $mapper = $this->mapper();
        [$all, $purchase, $transfer] = $mapper->itemsFromRows([
            CoreRequestMonitoring::row(['requestProcessID' => 1, 'requestProcessName' => '- ALL -']),
            CoreRequestMonitoring::row(),
            CoreRequestMonitoring::row(['requestProcessID' => 3, 'requestProcessName' => 'Transfer']),
        ]);

        $this->assertTrue($all->canBeOrdered());
        $this->assertTrue($purchase->canBeOrdered());
        $this->assertFalse($transfer->canBeOrdered());
    }

    #[Test]
    public function rows_without_a_request_number_product_or_quantity_are_dropped(): void
    {
        $items = $this->mapper()->itemsFromRows([
            CoreRequestMonitoring::row(['purchaseRequestNum' => '']),
            CoreRequestMonitoring::row(['productDetailID' => 0]),
            CoreRequestMonitoring::row(['sumQty' => 'n/a']),
            'garbage',
            CoreRequestMonitoring::row(['supplierName' => '', 'productCode' => null]),
        ]);

        $this->assertCount(1, $items);
        $this->assertNull($items[0]->supplierName);
        $this->assertNull($items[0]->productCode);
    }
}
