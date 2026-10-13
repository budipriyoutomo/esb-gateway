<?php

namespace App\Http\Controllers\SalesPushes;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalesPushes\ListSalesPushesRequest;
use App\Models\SalesPush;
use Inertia\Inertia;
use Inertia\Response;

/**
 * History of sales transactions POS clients pushed through the gateway
 * (ADR-022). Read from the gateway's own table; nothing here reaches the ESB.
 */
class SalesPushController extends Controller
{
    private const PER_PAGE = 25;

    public function index(ListSalesPushesRequest $request): Response
    {
        $filters = $request->filters();

        $pushes = SalesPush::query()
            ->with('apiClient:id,name')
            ->when($filters['salesNum'] ?? null, fn ($query, string $value) => $query->where('sales_num', $value))
            ->when($filters['branchCode'] ?? null, fn ($query, string $value) => $query->where('branch_code', $value))
            ->when($filters['status'] ?? null, fn ($query, string $value) => $query->where('status', $value))
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (SalesPush $push): array => $this->summary($push));

        return Inertia::render('sales-pushes/index', [
            'pushes' => $pushes,
            'filters' => $filters,
            'statuses' => SalesPush::STATUSES,
        ]);
    }

    public function show(SalesPush $salesPush): Response
    {
        $head = $salesPush->payload['salesHead'];

        return Inertia::render('sales-pushes/show', [
            'push' => [
                ...$this->summary($salesPush),
                'failure' => $salesPush->failure,
                'reference' => $salesPush->correlation_id,
                'errors' => $salesPush->errors,
                'esbStatus' => $salesPush->esb_status,
                'esbSyncDate' => $salesPush->esb_sync_date,
                'lastAttemptedAt' => $salesPush->last_attempted_at?->toIso8601String(),
                'updatedAt' => $salesPush->updated_at?->toIso8601String(),
                'canRetry' => $salesPush->canBeRetried(),
                // A summary, not the payload: the payment lines carry card
                // numbers and account names that no screen needs.
                'summary' => [
                    'billNum' => $this->scalar($head['billNum'] ?? null),
                    'salesDateIn' => $this->scalar($head['salesDateIn'] ?? null),
                    'salesDateOut' => $this->scalar($head['salesDateOut'] ?? null),
                    'visitPurposeName' => $this->scalar($head['visitPurposeName'] ?? null),
                    'statusId' => $this->scalar($head['statusID'] ?? null),
                    'grandTotal' => $this->number($head['grandTotal'] ?? null),
                    'paymentTotal' => $this->number($head['paymentTotal'] ?? null),
                    'menuLines' => is_array($head['menu'] ?? null) ? count($head['menu']) : 0,
                    'payments' => is_array($head['payment'] ?? null) ? count($head['payment']) : 0,
                ],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(SalesPush $push): array
    {
        return [
            'id' => $push->public_id,
            'salesNum' => $push->sales_num,
            'branchCode' => $push->branch_code,
            'salesDate' => $push->sales_date?->toDateString(),
            'client' => $push->apiClient?->name,
            'status' => $push->status,
            'attempts' => $push->attempts,
            'pushedAt' => $push->pushed_at?->toIso8601String(),
            'createdAt' => $push->created_at?->toIso8601String(),
        ];
    }

    private function scalar(mixed $value): ?string
    {
        return is_scalar($value) ? mb_substr((string) $value, 0, 100) : null;
    }

    private function number(mixed $value): int|float|null
    {
        return is_int($value) || is_float($value) ? $value : (is_numeric($value) ? (float) $value : null);
    }
}
