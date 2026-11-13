<?php

namespace App\Http\Controllers\ClosingRecaps;

use App\Actions\ClosingRecaps\ClosingMappingSet;
use App\Authorization\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClosingRecaps\ListClosingRecapsRequest;
use App\Models\ClosingItemJournal;
use App\Models\ClosingItemMapping;
use App\Models\ClosingPurposeMapping;
use App\Models\ClosingRecap;
use App\Models\ClosingRecapItem;
use App\Models\ClosingSalesPush;
use App\Models\ClosingSubmission;
use App\Support\Decimal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Closing recaps sync-api Maharasa sent, as held in the gateway, and the
 * mappings each still needs (ADR-032). Read from the gateway's own tables;
 * nothing here reaches the ESB.
 */
class ClosingRecapController extends Controller
{
    private const PER_PAGE = 25;

    public function index(ListClosingRecapsRequest $request): Response
    {
        $filters = $request->filters();
        $mappings = ClosingMappingSet::load();

        $recaps = ClosingRecap::query()
            ->with('items')
            ->when($filters['outletCode'] ?? null, fn ($query, string $value) => $query->where('outlet_code', mb_strtoupper($value)))
            ->when($filters['productionDate'] ?? null, fn ($query, string $value) => $query->whereDate('production_date', $value))
            ->when($filters['status'] ?? null, fn ($query, string $value) => $query->where('status', $value))
            ->orderByDesc('production_date')
            ->orderBy('outlet_code')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(function (ClosingRecap $recap) use ($mappings): array {
                $missing = $mappings->missingFor($recap);

                return [
                    ...$this->summary($recap),
                    'ready' => $missing === [],
                    'missing' => count($missing),
                ];
            });

        return Inertia::render('closing-recaps/index', [
            'recaps' => $recaps,
            'filters' => $filters,
            'statuses' => ClosingRecap::STATUSES,
        ]);
    }

    public function show(Request $request, ClosingRecap $closingRecap): Response
    {
        $closingRecap->load(['items' => fn ($query) => $query->orderBy('id')]);
        $mappings = ClosingMappingSet::load();
        $missing = $mappings->missingFor($closingRecap);

        $user = $request->user();
        $canProcess = $user !== null
            && $user->can(Permission::ProcessClosingRecaps->value)
            && $user->can(Permission::InvokeEsbOperations->value);
        $unsettled = $closingRecap->hasUnsettledJournal();

        // The newest draft that is not superseded: the one to send, or the
        // one sent.
        $salesDraft = $closingRecap->salesPushes()
            ->with('salesPush')
            ->where('status', '!=', ClosingSalesPush::STATUS_SUPERSEDED)
            ->latest('id')
            ->first();
        $salesSent = $salesDraft?->status === ClosingSalesPush::STATUS_SENT && $salesDraft->recap_sequence === $closingRecap->sequence;
        $salesGaps = array_values(array_filter(
            $missing,
            fn (array $gap): bool => in_array($gap['type'], ['outlet', 'item-menu'], true),
        ));

        $journals = $closingRecap->itemJournals()
            ->where('status', '!=', ClosingItemJournal::STATUS_SUPERSEDED)
            ->orderByDesc('recap_sequence')
            ->orderBy('id')
            ->get();

        $submissions = ClosingSubmission::query()
            ->with('apiClient:id,name')
            ->where('outlet_code', $closingRecap->outlet_code)
            ->whereDate('production_date', $closingRecap->production_date->toDateString())
            ->orderByDesc('sequence')
            ->get();

        return Inertia::render('closing-recaps/show', [
            'recap' => [
                ...$this->summary($closingRecap),
                'ready' => $missing === [],
                'missing' => $missing,
                'outlet' => $mappings->outlet($closingRecap->outlet_code)?->toView(),
                'items' => $closingRecap->items->map(fn (ClosingRecapItem $item): array => [
                    'source' => $item->source,
                    'key' => ClosingItemMapping::keyOf($item),
                    'code' => $item->source === ClosingRecapItem::SOURCE_CLOSING ? $item->menu_code : $item->product_code,
                    'name' => ClosingMappingSet::itemLabel($item),
                    'colorplateSold' => Decimal::trim($item->colorplate_sold),
                    'posQty' => Decimal::trim($item->pos_qty),
                    'totalSold' => Decimal::trim($item->total_sold),
                    'waste' => Decimal::trim($item->waste),
                    'adjustment' => Decimal::trim($item->adjustment),
                    'compensation' => Decimal::trim($item->compensation),
                    'esbMenu' => $mappings->item($item)?->menuLabel(),
                    'bom' => $mappings->item($item)?->bom_name,
                ])->values()->all(),
                'submissions' => $submissions->map(fn (ClosingSubmission $submission): array => [
                    'id' => $submission->public_id,
                    'sequence' => $submission->sequence,
                    'messageId' => $submission->message_id,
                    'sentAt' => $submission->sent_at->toIso8601String(),
                    'receivedAt' => $submission->created_at?->toIso8601String(),
                    'client' => $submission->apiClient?->name,
                    'current' => $submission->id === $closingRecap->closing_submission_id,
                ])->all(),
            ],
            'canProcess' => $canProcess,
            'actions' => [
                'prepare' => $canProcess && $missing === [] && ! $unsettled && ! $closingRecap->hasCreatedJournalForCurrentVersion(),
                'send' => $canProcess && ! $unsettled && $closingRecap->hasJournalsToSend(),
                'prepareSales' => $canProcess && $closingRecap->hasSalesData() && $salesGaps === [] && ! $salesSent,
                'sendSales' => $canProcess && $salesDraft?->status === ClosingSalesPush::STATUS_PREPARED && $salesDraft->recap_sequence === $closingRecap->sequence,
            ],
            'sales' => [
                'hasData' => $closingRecap->hasSalesData(),
                'grandTotal' => $closingRecap->sales['grandTotal'] ?? null,
                'payments' => count($closingRecap->payments ?? []),
                'draft' => $salesDraft === null ? null : [
                    'id' => $salesDraft->public_id,
                    'salesNum' => $salesDraft->sales_num,
                    'sequence' => $salesDraft->recap_sequence,
                    'current' => $salesDraft->recap_sequence === $closingRecap->sequence,
                    'status' => $salesDraft->status,
                    'menuLines' => count($salesDraft->payload['salesHead']['menu'] ?? []),
                    'grandTotal' => $salesDraft->payload['salesHead']['grandTotal'] ?? null,
                    'sentAt' => $salesDraft->sent_at?->toIso8601String(),
                    'push' => $salesDraft->salesPush === null ? null : [
                        'id' => $salesDraft->salesPush->public_id,
                        'status' => $salesDraft->salesPush->status,
                        'errors' => $salesDraft->salesPush->errors,
                    ],
                ],
            ],
            'journals' => $journals->map(fn (ClosingItemJournal $journal): array => [
                'id' => $journal->public_id,
                'kind' => $journal->kind,
                'label' => ClosingPurposeMapping::label($journal->kind),
                'sequence' => $journal->recap_sequence,
                'current' => $journal->recap_sequence === $closingRecap->sequence,
                'purposeName' => $journal->purpose_name,
                'direction' => $journal->direction,
                'status' => $journal->status,
                'number' => $journal->item_journal_num,
                'notes' => $journal->notes,
                'lines' => $journal->lines,
                'breakdown' => $journal->breakdown,
                'errors' => $journal->errors,
                'reference' => $journal->correlation_id,
                'sentAt' => $journal->sent_at?->toIso8601String(),
                'canResolve' => $canProcess && $journal->canBeResolved(),
            ])->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(ClosingRecap $recap): array
    {
        return [
            'id' => $recap->public_id,
            'outletCode' => $recap->outlet_code,
            'productionDate' => $recap->production_date->toDateString(),
            'sequence' => $recap->sequence,
            'items' => $recap->items->count(),
            'status' => $recap->status,
            'lastReceivedAt' => $recap->last_received_at?->toIso8601String(),
        ];
    }
}
