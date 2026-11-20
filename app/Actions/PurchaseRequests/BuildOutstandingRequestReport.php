<?php

namespace App\Actions\PurchaseRequests;

use App\Domain\Canonical\BranchQuery;
use App\Domain\Canonical\InventoryKind;
use App\Domain\Canonical\OutstandingRequestItem;
use App\Domain\Canonical\OutstandingRequestQuery;
use App\Domain\Canonical\OutstandingRequestReport;
use App\Domain\Canonical\OutstandingStage;
use App\Domain\Canonical\Pagination;
use App\Domain\Canonical\PurchaseOrder;
use App\Domain\Canonical\PurchaseOrderLine;
use App\Domain\Canonical\PurchaseOrderPage;
use App\Domain\Canonical\PurchaseOrderQuery;
use App\Domain\Canonical\PurchaseOrderSummary;
use App\Domain\Canonical\UnorderedRequestItem;
use App\Domain\Canonical\UnorderedRequestQuery;
use App\Integrations\Contracts\BranchSource;
use App\Integrations\Contracts\InventoryDocumentSource;
use App\Integrations\Contracts\PurchaseOrderSource;
use App\Integrations\Contracts\RequestMonitoringSource;
use App\Integrations\ESB\Exceptions\EsbException;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository as Cache;
use Throwable;

/**
 * The PR outstanding report (ADR-034): request items not yet ordered, from
 * request monitoring, and the lines of open purchase orders raised from a
 * request that are not received, or not as requested. What a line received
 * is summed from the order's goods receipts: the order's own `detailQty` is
 * not a received quantity. The ERP's figures, joined and classified here.
 *
 * The assembled items are cached per period and filter (not per page), so
 * paging and the export do not read the ERP again.
 */
class BuildOutstandingRequestReport
{
    /** Open purchase orders read in detail per report, at most. */
    public const MAX_ORDERS = 100;

    /**
     * Purchase order statuses still waiting for goods: 3 Authorized and 4
     * Receiving, as seen on staging (TBD, docs/esb/open-questions.md §6.2).
     * 8 Finished and 25 Closed are done; a closed order is not outstanding
     * even when short (owner decision 2026-10-28).
     */
    public const OPEN_ORDER_STATUSES = [3, 4];

    /**
     * Goods receipt statuses whose goods have not come in yet: 1 New and 2
     * Rejected. Received quantity counts once a receipt is authorized
     * (business confirmation 2026-10-28); 3 Authorized and 11 Invoice seen
     * on staging (TBD, docs/esb/open-questions.md §6.2).
     */
    public const UNCOUNTED_RECEIPT_STATUSES = [1, 2];

    private const CACHE_SECONDS = 300;

    /** Quantities closer than this are equal (the ERP sends decimals). */
    private const EPSILON = 0.00001;

    public function __construct(
        private readonly RequestMonitoringSource $requests,
        private readonly PurchaseOrderSource $orders,
        private readonly BranchSource $branches,
        private readonly InventoryDocumentSource $inventory,
        private readonly Cache $cache,
    ) {}

    /**
     * @throws TooManyOrdersForReport
     * @throws EsbException
     */
    public function __invoke(OutstandingRequestQuery $query, CarbonImmutable $today): OutstandingRequestReport
    {
        // Cached as plain arrays: the cache unserializes no classes.
        $cached = $this->cache->remember(
            $this->cacheKey($query, $today),
            self::CACHE_SECONDS,
            fn (): array => array_map(
                static fn (OutstandingRequestItem $item): array => $item->jsonSerialize(),
                $this->read($query, $today),
            ),
        );
        $items = array_map(OutstandingRequestItem::fromArray(...), $cached);

        $counts = array_fill_keys(array_map(static fn (OutstandingStage $stage): string => $stage->value, OutstandingStage::cases()), 0);
        $overdue = 0;

        foreach ($items as $item) {
            $counts[$item->stage->value]++;
            $overdue += $item->overdueDays > 0 ? 1 : 0;
        }

        $shown = array_values(array_filter(
            $items,
            static fn (OutstandingRequestItem $item): bool => ($query->stage === null || $item->stage === $query->stage)
                && (! $query->overdueOnly || $item->overdueDays > 0),
        ));

        return new OutstandingRequestReport(
            items: array_slice($shown, ($query->page - 1) * $query->limit, $query->limit),
            pagination: new Pagination($query->page, $query->limit, count($shown)),
            stageCounts: $counts,
            overdueCount: $overdue,
        );
    }

    /**
     * @return list<OutstandingRequestItem>
     */
    private function read(OutstandingRequestQuery $query, CarbonImmutable $today): array
    {
        // Read the order lists first: a refused report reads nothing else.
        $orders = $this->openOrders($query);
        $items = [];

        foreach ($this->unordered($query) as $request) {
            if ($request->canBeOrdered()) {
                $items[] = $this->fromRequest($request, $today);
            }
        }

        foreach ($orders as $summary) {
            $order = $this->orders->findPurchaseOrder($summary->number);
            $received = $this->receivedByProduct($order);

            foreach ($order->lines as $line) {
                // A product on several lines fills them in order.
                $id = $line->productDetailId ?? 0;
                $take = min($received[$id] ?? 0.0, max(0.0, $line->qty ?? 0.0));
                $received[$id] = ($received[$id] ?? 0.0) - $take;

                if ($this->isLastLineOf($order, $line)) {
                    $take += $received[$id];
                    $received[$id] = 0.0;
                }

                $item = $this->fromLine($order, $line, $take, $today);

                if ($item !== null) {
                    $items[] = $item;
                }
            }
        }

        usort($items, static function (OutstandingRequestItem $a, OutstandingRequestItem $b): int {
            $stages = OutstandingStage::cases();

            return [$b->overdueDays, $a->requiredDate ?? '9999', array_search($a->stage, $stages, true), $a->purchaseRequestNumbers]
                <=> [$a->overdueDays, $b->requiredDate ?? '9999', array_search($b->stage, $stages, true), $b->purchaseRequestNumbers];
        });

        return $items;
    }

    /**
     * @return list<UnorderedRequestItem>
     */
    private function unordered(OutstandingRequestQuery $query): array
    {
        // The ERP requires at least one branch: no branch means all of them.
        $branchIds = $query->branchId !== null ? [$query->branchId] : $this->allBranchIds();

        if ($branchIds === []) {
            return [];
        }

        return $this->requests->unorderedItems(new UnorderedRequestQuery(
            branchIds: $branchIds,
            requiredDateFrom: $query->requiredDateFrom,
            requiredDateTo: $query->requiredDateTo,
            purchaseRequestNumber: $query->purchaseRequestNumber,
        ));
    }

    /**
     * @return list<int>
     */
    private function allBranchIds(): array
    {
        $ids = [];
        $page = 1;

        do {
            $result = $this->branches->listBranches(new BranchQuery(page: $page, limit: BranchQuery::MAX_LIMIT));

            foreach ($result->branches as $branch) {
                $ids[] = $branch->id;
            }

            $page++;
        } while ($result->pagination->hasMorePages());

        return $ids;
    }

    /**
     * Every open order of the period raised from a request. MAX_ORDERS is
     * one page at the largest page size, so one page per status holds them
     * all; more is refused.
     *
     * @return list<PurchaseOrderSummary>
     *
     * @throws TooManyOrdersForReport
     */
    private function openOrders(OutstandingRequestQuery $query): array
    {
        $pages = array_map(
            fn (int $status) => $this->orders->listPurchaseOrders($this->orderQuery($query, $status)),
            self::OPEN_ORDER_STATUSES,
        );

        $total = array_sum(array_map(static fn (PurchaseOrderPage $page): int => $page->pagination->count ?? count($page->purchaseOrders), $pages));

        if ($total > self::MAX_ORDERS) {
            throw new TooManyOrdersForReport($total);
        }

        $byNumber = [];

        foreach ($pages as $page) {
            foreach ($page->purchaseOrders as $summary) {
                if ($summary->number !== '' && trim((string) $summary->purchaseRequestNumbers) !== '') {
                    $byNumber[$summary->number] = $summary;
                }
            }
        }

        return array_values($byNumber);
    }

    private function orderQuery(OutstandingRequestQuery $query, int $status): PurchaseOrderQuery
    {
        return new PurchaseOrderQuery(
            page: 1,
            limit: PurchaseOrderQuery::MAX_LIMIT,
            requiredDateFrom: $query->requiredDateFrom,
            requiredDateTo: $query->requiredDateTo,
            branchIds: $query->branchId === null ? [] : [$query->branchId],
            statusId: $status,
            purchaseRequestNumber: $query->purchaseRequestNumber,
        );
    }

    private function fromRequest(UnorderedRequestItem $request, CarbonImmutable $today): OutstandingRequestItem
    {
        return new OutstandingRequestItem(
            stage: OutstandingStage::NotOrdered,
            purchaseRequestNumbers: $request->purchaseRequestNumber,
            purchaseRequestDate: $request->purchaseRequestDate,
            purchaseOrderNumber: null,
            purchaseOrderDate: null,
            purchaseOrderStatus: null,
            requiredDate: $request->requiredDate,
            branchName: $request->branchName,
            supplierName: $request->supplierName,
            productCode: $request->productCode,
            productName: $request->productName,
            uomName: $request->uomName,
            requestedQty: null,
            orderedQty: null,
            receivedQty: null,
            outstandingQty: $request->unorderedQty,
            ageDays: $this->daysSince($request->purchaseRequestDate, $today),
            overdueDays: max(0, $this->daysSince($request->requiredDate, $today) ?? 0),
        );
    }

    /**
     * What the order's counted goods receipts brought in, per product. The
     * receipt lines are in the order's units (seen on staging 2026-11-19).
     *
     * @return array<int, float> productDetailId => quantity
     */
    private function receivedByProduct(PurchaseOrder $order): array
    {
        $received = [];

        foreach ($order->goodsReceipts as $linked) {
            $receipt = $this->inventory->findDocument(InventoryKind::GoodsReceipts, $linked->number);

            if ($receipt === null || in_array($receipt->statusId, self::UNCOUNTED_RECEIPT_STATUSES, true)) {
                continue;
            }

            foreach ($receipt->lines as $line) {
                if ($line->productDetailId !== null && $line->qty !== null) {
                    $received[$line->productDetailId] = ($received[$line->productDetailId] ?? 0.0) + $line->qty;
                }
            }
        }

        return $received;
    }

    private function isLastLineOf(PurchaseOrder $order, PurchaseOrderLine $line): bool
    {
        foreach (array_reverse($order->lines) as $candidate) {
            if ($candidate->productDetailId === $line->productDetailId) {
                return $candidate === $line;
            }
        }

        return false;
    }

    /**
     * A line still waiting for goods, or received but not as requested; null
     * when it is done. A requested quantity of 0 means the product was not
     * asked for, so it cannot be "not as requested".
     */
    private function fromLine(PurchaseOrder $order, PurchaseOrderLine $line, float $received, CarbonImmutable $today): ?OutstandingRequestItem
    {
        $ordered = $line->qty;

        if ($ordered === null || $ordered <= 0) {
            return null;
        }

        $requested = $line->requestedQty !== null && $line->requestedQty > 0 ? $line->requestedQty : null;
        $complete = $line->fullyReceived === true || $received >= $ordered - self::EPSILON;

        if (! $complete) {
            $stage = $received <= self::EPSILON ? OutstandingStage::NotReceived : OutstandingStage::PartiallyReceived;
            $outstanding = $ordered - $received;
        } elseif ($requested !== null && abs($requested - $received) > self::EPSILON) {
            $stage = OutstandingStage::NotAsRequested;
            $outstanding = $requested - $received;
        } else {
            return null;
        }

        return new OutstandingRequestItem(
            stage: $stage,
            purchaseRequestNumbers: $order->purchaseRequestNumbers,
            purchaseRequestDate: null,
            purchaseOrderNumber: $order->number,
            purchaseOrderDate: $order->date,
            purchaseOrderStatus: $order->statusName,
            requiredDate: $order->requiredDate,
            branchName: $order->branchName,
            supplierName: $order->supplierName,
            productCode: $line->productCode === '' ? null : $line->productCode,
            productName: $line->productName === '' ? null : $line->productName,
            uomName: $line->uomName,
            requestedQty: $requested,
            orderedQty: $ordered,
            receivedQty: round($received, 4),
            outstandingQty: round($outstanding, 4),
            ageDays: $this->daysSince($order->date, $today),
            overdueDays: max(0, $this->daysSince($order->requiredDate, $today) ?? 0),
        );
    }

    /** Whole days from an ERP date (its first ten characters, YYYY-MM-DD) to today. */
    private function daysSince(?string $date, CarbonImmutable $today): ?int
    {
        if ($date === null || preg_match('/^\d{4}-\d{2}-\d{2}/', $date) !== 1) {
            return null;
        }

        try {
            $day = CarbonImmutable::createFromFormat('!Y-m-d', substr($date, 0, 10));
        } catch (Throwable) {
            return null;
        }

        return $day === null ? null : (int) $day->diffInDays($today->startOfDay(), false);
    }

    private function cacheKey(OutstandingRequestQuery $query, CarbonImmutable $today): string
    {
        // Ages change with the day, so the day is part of the key.
        return 'pr-outstanding:v2:'.sha1((string) json_encode([
            $query->requiredDateFrom,
            $query->requiredDateTo,
            $query->branchId,
            $query->purchaseRequestNumber,
            $today->toDateString(),
        ]));
    }
}
