<?php

namespace Tests\Unit\Actions;

use App\Actions\PurchaseRequests\BuildOutstandingRequestReport;
use App\Actions\PurchaseRequests\TooManyOrdersForReport;
use App\Domain\Canonical\ApprovalDecision;
use App\Domain\Canonical\BranchDetail;
use App\Domain\Canonical\BranchPage;
use App\Domain\Canonical\BranchQuery;
use App\Domain\Canonical\BranchSummary;
use App\Domain\Canonical\InventoryCarryOver;
use App\Domain\Canonical\InventoryDocument;
use App\Domain\Canonical\InventoryDocumentPage;
use App\Domain\Canonical\InventoryDocumentQuery;
use App\Domain\Canonical\InventoryDraft;
use App\Domain\Canonical\InventoryKind;
use App\Domain\Canonical\InventoryLine;
use App\Domain\Canonical\OutstandingRequestQuery;
use App\Domain\Canonical\OutstandingStage;
use App\Domain\Canonical\Pagination;
use App\Domain\Canonical\PurchaseOrder;
use App\Domain\Canonical\PurchaseOrderLine;
use App\Domain\Canonical\PurchaseOrderLinkedDocument;
use App\Domain\Canonical\PurchaseOrderPage;
use App\Domain\Canonical\PurchaseOrderQuery;
use App\Domain\Canonical\PurchaseOrderSummary;
use App\Domain\Canonical\UnorderedRequestItem;
use App\Domain\Canonical\UnorderedRequestQuery;
use App\Integrations\Contracts\BranchSource;
use App\Integrations\Contracts\InventoryDocumentSource;
use App\Integrations\Contracts\PurchaseOrderSource;
use App\Integrations\Contracts\RequestMonitoringSource;
use Carbon\CarbonImmutable;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The PR outstanding report (ADR-034): stages, bounds, ordering and paging,
 * over in-memory sources.
 */
class BuildOutstandingRequestReportTest extends TestCase
{
    /** @var list<UnorderedRequestItem> */
    private array $unordered = [];

    /** @var array<int, list<PurchaseOrderSummary>> statusId => summaries */
    private array $summaries = [];

    /** @var array<string, PurchaseOrder> */
    private array $orders = [];

    /** @var list<UnorderedRequestQuery> */
    private array $monitoringQueries = [];

    /** @var list<PurchaseOrderQuery> */
    private array $orderQueries = [];

    /** @var list<string> */
    private array $detailReads = [];

    /** @var array<string, InventoryDocument> goods receipts by number */
    private array $receipts = [];

    /** @var list<string> */
    private array $receiptReads = [];

    private Repository $cache;

    protected function setUp(): void
    {
        parent::setUp();

        // Serialized with no classes allowed, as config/cache.php sets
        // serializable_classes for the real stores.
        $this->cache = new Repository(new ArrayStore(serializesValues: true, serializableClasses: false));
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-04-01');
    }

    private function query(array $overrides = []): OutstandingRequestQuery
    {
        return new OutstandingRequestQuery(...[
            'requiredDateFrom' => '2026-03-01',
            'requiredDateTo' => '2026-03-31',
            'branchId' => 3,
            ...$overrides,
        ]);
    }

    private function unordered(array $overrides = []): UnorderedRequestItem
    {
        return new UnorderedRequestItem(...[
            'purchaseRequestNumber' => 'PR-1',
            'purchaseRequestDate' => '2026-03-18',
            'requiredDate' => '2026-03-25',
            'branchId' => 3,
            'branchName' => 'Outlet Senopati',
            'productDetailId' => 901,
            'productCode' => 'Air_mineral',
            'productName' => 'Air mineral',
            'uomName' => 'Bottle',
            'unorderedQty' => 20.0,
            'requestProcessId' => 2,
            'requestProcessName' => 'Purchase',
            'supplierName' => 'PT Sumber Air',
            ...$overrides,
        ]);
    }

    /**
     * A purchase order line and how much of it the order's goods receipt
     * brought in (null: none).
     *
     * @return array{PurchaseOrderLine, float|null}
     */
    private function line(float $qty, ?float $received, ?float $requested = null, ?bool $full = false, int $id = 901): array
    {
        return [
            new PurchaseOrderLine(
                productDetailId: $id,
                productCode: 'P'.$id,
                productName: 'Product '.$id,
                qty: $qty,
                requestedQty: $requested ?? $qty,
                uomName: 'Pcs',
                fullyReceived: $full,
            ),
            $received,
        ];
    }

    /**
     * @param  array<int, float>  $qtyByProduct
     */
    private function receipt(string $number, int $status, array $qtyByProduct): PurchaseOrderLinkedDocument
    {
        $this->receipts[$number] = new InventoryDocument(
            provider: 'esb',
            kind: InventoryKind::GoodsReceipts->value,
            number: $number,
            date: '2026-03-24',
            statusId: $status,
            statusName: null,
            lines: array_map(
                static fn (int $id, float $qty): InventoryLine => new InventoryLine($id, 'P'.$id, null, 'Pcs', $qty),
                array_keys($qtyByProduct),
                $qtyByProduct,
            ),
        );

        return new PurchaseOrderLinkedDocument($number);
    }

    /**
     * An open order; what its lines received comes in on one authorized
     * goods receipt, after any receipts given.
     *
     * @param  list<array{PurchaseOrderLine, float|null}>  $lines
     * @param  list<PurchaseOrderLinkedDocument>  $receipts
     */
    private function order(string $number, int $status, array $lines, ?string $prs = 'PR-1', string $date = '2026-03-20', string $required = '2026-03-25', array $receipts = []): void
    {
        $received = [];

        foreach ($lines as [$line, $qty]) {
            if ($qty !== null && $qty > 0) {
                $received[$line->productDetailId] = ($received[$line->productDetailId] ?? 0.0) + $qty;
            }
        }

        if ($received !== []) {
            $receipts[] = $this->receipt('GR-'.$number, 3, $received);
        }

        $this->summaries[$status][] = new PurchaseOrderSummary(provider: 'esb', number: $number, statusId: $status, purchaseRequestNumbers: $prs);
        $this->orders[$number] = new PurchaseOrder(
            provider: 'esb',
            number: $number,
            date: $date,
            requiredDate: $required,
            branchName: 'Outlet Senopati',
            supplierName: 'PT Sumber Air',
            statusId: $status,
            statusName: $status === 3 ? 'Authorized' : 'Receiving',
            purchaseRequestNumbers: $prs,
            lines: array_map(static fn (array $pair): PurchaseOrderLine => $pair[0], $lines),
            goodsReceipts: $receipts,
        );
    }

    private function action(): BuildOutstandingRequestReport
    {
        $test = $this;

        $requests = new class($test) implements RequestMonitoringSource
        {
            public function __construct(private BuildOutstandingRequestReportTest $test) {}

            public function provider(): string
            {
                return 'esb';
            }

            public function unorderedItems(UnorderedRequestQuery $query): array
            {
                return $this->test->monitoring($query);
            }
        };

        $orders = new class($test) implements PurchaseOrderSource
        {
            public function __construct(private BuildOutstandingRequestReportTest $test) {}

            public function provider(): string
            {
                return 'esb';
            }

            public function listPurchaseOrders(PurchaseOrderQuery $query): PurchaseOrderPage
            {
                return $this->test->listOrders($query);
            }

            public function findPurchaseOrder(string $number): PurchaseOrder
            {
                return $this->test->findOrder($number);
            }

            public function decidePurchaseOrder(string $number, ApprovalDecision $decision, ?string $reason = null): void
            {
                throw new LogicException('The report never writes.');
            }

            public function deletePurchaseOrder(string $number): void
            {
                throw new LogicException('The report never writes.');
            }
        };

        $branches = new class implements BranchSource
        {
            public function provider(): string
            {
                return 'esb';
            }

            public function listBranches(BranchQuery $query): BranchPage
            {
                return BranchPage::fromAll([
                    new BranchSummary('esb', 3, 'Outlet Senopati'),
                    new BranchSummary('esb', 7, 'Gudang Pusat'),
                ], $query);
            }

            public function findBranch(int $id): ?BranchDetail
            {
                return null;
            }
        };

        $inventory = new class($test) implements InventoryDocumentSource
        {
            public function __construct(private BuildOutstandingRequestReportTest $test) {}

            public function provider(): string
            {
                return 'esb';
            }

            public function findDocument(InventoryKind $kind, string $number): ?InventoryDocument
            {
                return $this->test->findReceipt($kind, $number);
            }

            public function listDocuments(InventoryKind $kind, InventoryDocumentQuery $query): InventoryDocumentPage
            {
                throw new LogicException('The report reads receipts by number.');
            }

            public function decideDocument(InventoryKind $kind, string $number, ApprovalDecision $decision, ?string $reason = null): void
            {
                throw new LogicException('The report never writes.');
            }

            public function deleteDocument(InventoryKind $kind, string $number): void
            {
                throw new LogicException('The report never writes.');
            }

            public function createDocument(InventoryKind $kind, InventoryDraft $draft): ?string
            {
                throw new LogicException('The report never writes.');
            }

            public function updateDocument(InventoryKind $kind, string $number, InventoryDraft $draft, InventoryCarryOver $carryOver): void
            {
                throw new LogicException('The report never writes.');
            }

            public function attachFiles(InventoryKind $kind, string $number, array $files): void
            {
                throw new LogicException('The report never writes.');
            }

            public function deleteAttachments(InventoryKind $kind, string $number): void
            {
                throw new LogicException('The report never writes.');
            }

            public function completeTransferRequest(string $number, bool $finished): void
            {
                throw new LogicException('The report never writes.');
            }
        };

        return new BuildOutstandingRequestReport($requests, $orders, $branches, $inventory, $this->cache);
    }

    /** @return list<UnorderedRequestItem> */
    public function monitoring(UnorderedRequestQuery $query): array
    {
        $this->monitoringQueries[] = $query;

        return $this->unordered;
    }

    public function listOrders(PurchaseOrderQuery $query): PurchaseOrderPage
    {
        $this->orderQueries[] = $query;
        $all = $this->summaries[$query->statusId] ?? [];

        return new PurchaseOrderPage(
            purchaseOrders: array_slice($all, ($query->page - 1) * $query->limit, $query->limit),
            pagination: new Pagination($query->page, $query->limit, count($all)),
        );
    }

    public function findOrder(string $number): PurchaseOrder
    {
        $this->detailReads[] = $number;

        return $this->orders[$number];
    }

    public function findReceipt(InventoryKind $kind, string $number): ?InventoryDocument
    {
        $this->assertSame(InventoryKind::GoodsReceipts, $kind);
        $this->receiptReads[] = $number;

        return $this->receipts[$number] ?? null;
    }

    #[Test]
    public function every_item_is_classified_into_its_stage_with_its_outstanding_quantity(): void
    {
        $this->unordered = [$this->unordered()];
        $this->order('PO-1', 3, [
            $this->line(10, 0, id: 1),             // not received
            $this->line(10, 4, id: 2),             // partially received
            $this->line(10, 10, 12, id: 3),        // received in full, 12 requested
            $this->line(10, 10, id: 4),            // done: not outstanding
            $this->line(10, 6, 10, true, id: 5),   // ERP says full, 10 requested
        ]);

        $report = ($this->action())($this->query(), $this->today());
        $byProduct = [];

        foreach ($report->items as $item) {
            $byProduct[$item->productCode ?? $item->productName] = $item;
        }

        $this->assertCount(5, $report->items);
        $this->assertSame(OutstandingStage::NotOrdered, $byProduct['Air_mineral']->stage);
        $this->assertSame(20.0, $byProduct['Air_mineral']->outstandingQty);
        $this->assertNull($byProduct['Air_mineral']->purchaseOrderNumber);
        $this->assertSame(14, $byProduct['Air_mineral']->ageDays);
        $this->assertSame(7, $byProduct['Air_mineral']->overdueDays);

        $this->assertSame(OutstandingStage::NotReceived, $byProduct['P1']->stage);
        $this->assertSame(10.0, $byProduct['P1']->outstandingQty);
        $this->assertSame('PO-1', $byProduct['P1']->purchaseOrderNumber);
        $this->assertSame('PR-1', $byProduct['P1']->purchaseRequestNumbers);
        $this->assertSame(12, $byProduct['P1']->ageDays);

        $this->assertSame(OutstandingStage::PartiallyReceived, $byProduct['P2']->stage);
        $this->assertSame(6.0, $byProduct['P2']->outstandingQty);
        $this->assertSame(4.0, $byProduct['P2']->receivedQty);

        $this->assertSame(OutstandingStage::NotAsRequested, $byProduct['P3']->stage);
        $this->assertSame(2.0, $byProduct['P3']->outstandingQty);
        $this->assertSame(12.0, $byProduct['P3']->requestedQty);

        $this->assertArrayNotHasKey('P4', $byProduct);

        $this->assertSame(OutstandingStage::NotAsRequested, $byProduct['P5']->stage);
        $this->assertSame(4.0, $byProduct['P5']->outstandingQty);
    }

    #[Test]
    public function only_authorized_receipts_count_and_an_order_without_receipts_reads_none(): void
    {
        $this->order('PO-1', 4, [$this->line(10, 4)], receipts: [
            $this->receipt('GR-NEW', 1, [901 => 5.0]),
            $this->receipt('GR-REJECTED', 2, [901 => 5.0]),
            $this->receipt('GR-INVOICED', 11, [901 => 1.0]),
        ]);
        $this->order('PO-2', 3, [$this->line(10, null, id: 902)]);

        $report = ($this->action())($this->query(), $this->today());
        $byOrder = [];

        foreach ($report->items as $item) {
            $byOrder[$item->purchaseOrderNumber] = $item;
        }

        $this->assertSame(5.0, $byOrder['PO-1']->receivedQty);
        $this->assertSame(5.0, $byOrder['PO-1']->outstandingQty);
        $this->assertSame(OutstandingStage::NotReceived, $byOrder['PO-2']->stage);
        $this->assertSame(0.0, $byOrder['PO-2']->receivedQty);
        $this->assertSame(['GR-NEW', 'GR-REJECTED', 'GR-INVOICED', 'GR-PO-1'], $this->receiptReads);
    }

    #[Test]
    public function a_product_on_two_lines_is_received_into_the_first_line_first(): void
    {
        $this->order('PO-1', 4, [$this->line(10, 12, id: 901), $this->line(5, null, id: 901)]);

        $report = ($this->action())($this->query(), $this->today());

        $this->assertCount(1, $report->items);
        $this->assertSame(OutstandingStage::PartiallyReceived, $report->items[0]->stage);
        $this->assertSame(5.0, $report->items[0]->orderedQty);
        $this->assertSame(2.0, $report->items[0]->receivedQty);
        $this->assertSame(3.0, $report->items[0]->outstandingQty);
    }

    #[Test]
    public function a_line_with_no_requested_quantity_is_never_not_as_requested(): void
    {
        // requestQty 0: a product added to the order, not asked for.
        $this->order('PO-1', 4, [$this->line(10, 10, 0.0)]);

        $report = ($this->action())($this->query(), $this->today());

        $this->assertSame([], $report->items);
    }

    #[Test]
    public function transfer_items_and_orders_without_a_request_are_left_out(): void
    {
        $this->unordered = [$this->unordered(['requestProcessId' => 3, 'requestProcessName' => 'Transfer'])];
        $this->order('PO-NOPR', 3, [$this->line(10, 0)], prs: null);
        $this->order('PO-BLANK', 4, [$this->line(10, 0)], prs: ' ');

        $report = ($this->action())($this->query(), $this->today());

        $this->assertSame([], $report->items);
        $this->assertSame([], $this->detailReads);
    }

    #[Test]
    public function only_authorized_and_receiving_orders_are_read_with_the_report_filters(): void
    {
        ($this->action())($this->query(['purchaseRequestNumber' => 'PR-9']), $this->today());

        $this->assertSame([3, 4], array_map(fn (PurchaseOrderQuery $q): ?int => $q->statusId, $this->orderQueries));

        foreach ($this->orderQueries as $query) {
            $this->assertSame('2026-03-01', $query->requiredDateFrom);
            $this->assertSame('2026-03-31', $query->requiredDateTo);
            $this->assertSame([3], $query->branchIds);
            $this->assertSame('PR-9', $query->purchaseRequestNumber);
            $this->assertSame(PurchaseOrderQuery::MAX_LIMIT, $query->limit);
        }

        [$monitoring] = $this->monitoringQueries;
        $this->assertSame([3], $monitoring->branchIds);
        $this->assertSame('2026-03-01', $monitoring->requiredDateFrom);
        $this->assertSame('2026-03-31', $monitoring->requiredDateTo);
        $this->assertSame('PR-9', $monitoring->purchaseRequestNumber);
    }

    #[Test]
    public function without_a_branch_every_branch_is_asked_of_request_monitoring(): void
    {
        ($this->action())($this->query(['branchId' => null]), $this->today());

        $this->assertSame([3, 7], $this->monitoringQueries[0]->branchIds);
        $this->assertSame([], $this->orderQueries[0]->branchIds);
    }

    #[Test]
    public function up_to_the_cap_one_page_per_status_holds_every_open_order(): void
    {
        $this->assertSame(PurchaseOrderQuery::MAX_LIMIT, BuildOutstandingRequestReport::MAX_ORDERS);

        for ($i = 1; $i <= BuildOutstandingRequestReport::MAX_ORDERS; $i++) {
            $this->order('PO-'.$i, 4, [$this->line(1, 0)]);
        }

        $report = ($this->action())($this->query(['limit' => 100]), $this->today());

        $this->assertSame([[3, 1], [4, 1]], array_map(fn (PurchaseOrderQuery $q): array => [$q->statusId, $q->page], $this->orderQueries));
        $this->assertCount(BuildOutstandingRequestReport::MAX_ORDERS, $this->detailReads);
        $this->assertSame(100, $report->pagination->count);
    }

    #[Test]
    public function more_open_orders_than_the_cap_are_refused_before_any_detail_is_read(): void
    {
        for ($i = 1; $i <= BuildOutstandingRequestReport::MAX_ORDERS + 1; $i++) {
            $this->order('PO-'.$i, $i % 2 === 0 ? 3 : 4, [$this->line(1, 0)]);
        }

        try {
            ($this->action())($this->query(), $this->today());
            $this->fail('Expected the report to be refused.');
        } catch (TooManyOrdersForReport $exception) {
            $this->assertSame(BuildOutstandingRequestReport::MAX_ORDERS + 1, $exception->count);
        }

        $this->assertSame([], $this->detailReads);
    }

    #[Test]
    public function the_most_overdue_come_first_and_counts_cover_every_stage(): void
    {
        $this->unordered = [
            $this->unordered(['purchaseRequestNumber' => 'PR-LATE', 'requiredDate' => '2026-03-02']),
            $this->unordered(['purchaseRequestNumber' => 'PR-NOT-YET', 'requiredDate' => '2026-04-05']),
        ];
        $this->order('PO-1', 4, [$this->line(10, 4)], required: '2026-03-20');

        $report = ($this->action())($this->query(), $this->today());

        $this->assertSame(['PR-LATE', 'PR-1', 'PR-NOT-YET'], array_map(fn ($item) => $item->purchaseRequestNumbers, $report->items));
        $this->assertSame(0, $report->items[2]->overdueDays);
        $this->assertSame(['not-ordered' => 2, 'not-received' => 0, 'partially-received' => 1, 'not-as-requested' => 0], $report->stageCounts);
        $this->assertSame(2, $report->overdueCount);
    }

    #[Test]
    public function the_stage_and_overdue_filters_narrow_the_rows_but_not_the_counts(): void
    {
        $this->unordered = [
            $this->unordered(['requiredDate' => '2026-03-02']),
            $this->unordered(['purchaseRequestNumber' => 'PR-NOT-YET', 'requiredDate' => '2026-04-05']),
        ];
        $this->order('PO-1', 4, [$this->line(10, 4)]);

        $stage = ($this->action())($this->query(['stage' => OutstandingStage::NotOrdered, 'overdueOnly' => true]), $this->today());

        $this->assertCount(1, $stage->items);
        $this->assertSame(OutstandingStage::NotOrdered, $stage->items[0]->stage);
        $this->assertSame(1, $stage->pagination->count);
        $this->assertSame(2, $stage->stageCounts['not-ordered']);
    }

    #[Test]
    public function rows_are_paged_by_the_gateway_from_one_cached_read(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->unordered[] = $this->unordered(['purchaseRequestNumber' => 'PR-'.$i]);
        }

        $first = ($this->action())($this->query(['limit' => 2]), $this->today());
        $second = ($this->action())($this->query(['limit' => 2, 'page' => 2]), $this->today());

        $this->assertCount(2, $first->items);
        $this->assertCount(1, $second->items);
        $this->assertSame(3, $second->pagination->count);
        $this->assertSame(2, $second->pagination->page);
        $this->assertCount(1, $this->monitoringQueries);
    }
}
