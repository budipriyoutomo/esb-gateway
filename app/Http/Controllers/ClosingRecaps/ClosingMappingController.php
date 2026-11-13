<?php

namespace App\Http\Controllers\ClosingRecaps;

use App\Actions\ClosingRecaps\SaveClosingMapping;
use App\Authorization\Permission;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClosingRecaps\SaveClosingItemMappingRequest;
use App\Http\Requests\ClosingRecaps\SaveClosingOutletMappingRequest;
use App\Http\Requests\ClosingRecaps\SaveClosingPurposeMappingRequest;
use App\Models\ClosingItemMapping;
use App\Models\ClosingOutletMapping;
use App\Models\ClosingPurposeMapping;
use App\Models\ClosingRecap;
use App\Models\ClosingRecapItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use stdClass;

/**
 * The closing mappings (ADR-032): outlets, items and purposes. Seeing them
 * takes closing-recaps.view (route middleware); changing them takes
 * closing-mappings.manage (the form requests, and here for deletes).
 */
class ClosingMappingController extends Controller
{
    private const ITEMS_PER_PAGE = 50;

    public function __construct(private readonly SaveClosingMapping $save) {}

    // ============================================================ outlets

    public function outlets(Request $request): Response
    {
        $seen = ClosingRecap::query()
            ->select('outlet_code', DB::raw('count(*) as recaps'), DB::raw('max(production_date) as last_date'))
            ->groupBy('outlet_code')
            ->get()
            ->keyBy('outlet_code');

        $mapped = ClosingOutletMapping::query()->get()->keyBy('outlet_code');

        $codes = $seen->keys()->merge($mapped->keys())->unique()->sort()->values();

        return Inertia::render('closing-mappings/outlets', [
            'canManage' => $this->canManage($request),
            'outlets' => $codes->map(fn (string $code): array => [
                'outletCode' => $code,
                'recaps' => (int) ($seen->get($code)?->getAttribute('recaps') ?? 0),
                'lastProductionDate' => self::dateOrNull($seen->get($code)?->getAttribute('last_date')),
                'mapping' => $mapped->get($code)?->toView(),
            ])->all(),
        ]);
    }

    public function updateOutlet(SaveClosingOutletMappingRequest $request, string $outletCode): RedirectResponse
    {
        $this->save->outlet(self::outletCode($outletCode), $request->values(), $request->user()?->id);

        $this->toast('success', 'Outlet mapping saved.');

        return back();
    }

    public function destroyOutlet(Request $request, string $outletCode): RedirectResponse
    {
        $this->authorizeManage($request);
        $code = self::outletCode($outletCode);

        $this->save->delete(
            ClosingOutletMapping::query()->where('outlet_code', $code)->firstOrFail(),
            'outlet',
            $code,
        );

        $this->toast('success', 'Outlet mapping removed.');

        return back();
    }

    // ============================================================== items

    public function items(Request $request): Response
    {
        $validated = $request->validate([
            'source' => ['sometimes', 'nullable', 'string', 'in:'.implode(',', ClosingRecapItem::SOURCES)],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'unmapped' => ['sometimes', 'nullable', 'boolean'],
        ]);
        $filters = array_filter(
            array_map(static fn (mixed $value): string => is_scalar($value) ? trim((string) $value) : '', $validated),
            static fn (string $value): bool => $value !== '' && $value !== '0',
        );

        // The mapping key (ClosingItemMapping::keyFor): menu_id is stored
        // lower-cased already; a cast, because PostgreSQL's uuid has no LOWER().
        $key = "CASE WHEN i.source = 'closing' THEN CAST(i.menu_id AS VARCHAR(50)) ELSE UPPER(i.product_code) END";

        $rows = DB::table('closing_recap_items as i')
            ->leftJoin('closing_item_mappings as m', function ($join) use ($key): void {
                $join->on('m.source', '=', 'i.source')->on('m.item_key', '=', DB::raw($key));
            })
            ->select([
                'i.source',
                DB::raw("{$key} as item_key"),
                DB::raw('MAX(COALESCE(i.menu_code, i.product_code)) as code'),
                DB::raw('MAX(COALESCE(i.menu_name, i.product_name)) as name'),
                DB::raw('COUNT(DISTINCT i.closing_recap_id) as recaps'),
            ])
            ->when($filters['source'] ?? null, fn ($query, string $source) => $query->where('i.source', $source))
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $like = '%'.mb_strtolower(str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search)).'%';
                $query->where(function ($q) use ($like): void {
                    foreach (['i.menu_code', 'i.menu_name', 'i.product_code', 'i.product_name'] as $column) {
                        $q->orWhereRaw("LOWER({$column}) LIKE ?", [$like]);
                    }
                });
            })
            ->when(isset($filters['unmapped']), fn ($query) => $query->whereNull('m.id'))
            ->groupBy('i.source', DB::raw($key))
            ->orderBy('i.source')
            ->orderByRaw('MAX(COALESCE(i.menu_name, i.product_name))')
            ->paginate(self::ITEMS_PER_PAGE)
            ->withQueryString();

        $mappings = ClosingItemMapping::query()
            ->whereIn('item_key', collect($rows->items())->pluck('item_key')->all())
            ->get()
            ->keyBy(fn (ClosingItemMapping $m): string => $m->source.'|'.$m->item_key);

        return Inertia::render('closing-mappings/items', [
            'canManage' => $this->canManage($request),
            'filters' => $filters,
            'items' => $rows->through(fn (stdClass $row): array => [
                'source' => (string) $row->source,
                'key' => (string) $row->item_key,
                'code' => $row->code === null ? null : (string) $row->code,
                'name' => $row->name === null ? null : (string) $row->name,
                'recaps' => (int) $row->recaps,
                'needsBom' => $row->source === ClosingRecapItem::SOURCE_CLOSING,
                'mapping' => $mappings->get($row->source.'|'.$row->item_key)?->toView(),
            ]),
        ]);
    }

    public function updateItem(SaveClosingItemMappingRequest $request, string $source, string $key): RedirectResponse
    {
        $this->save->item($source, self::itemKey($source, $key), $request->values(), $request->user()?->id);

        $this->toast('success', 'Item mapping saved.');

        return back();
    }

    public function destroyItem(Request $request, string $source, string $key): RedirectResponse
    {
        $this->authorizeManage($request);
        $itemKey = self::itemKey($source, $key);

        $this->save->delete(
            ClosingItemMapping::query()->where('source', $source)->where('item_key', $itemKey)->firstOrFail(),
            'item',
            $source.':'.$itemKey,
        );

        $this->toast('success', 'Item mapping removed.');

        return back();
    }

    // =========================================================== purposes

    public function purposes(Request $request): Response
    {
        $mapped = ClosingPurposeMapping::query()->get()->keyBy('kind');

        return Inertia::render('closing-mappings/purposes', [
            'canManage' => $this->canManage($request),
            'purposes' => array_map(fn (string $kind): array => [
                'kind' => $kind,
                'label' => ClosingPurposeMapping::label($kind),
                'mapping' => $mapped->get($kind)?->toView(),
            ], array_keys(ClosingPurposeMapping::KINDS)),
        ]);
    }

    public function updatePurpose(SaveClosingPurposeMappingRequest $request, string $kind): RedirectResponse
    {
        $this->save->purpose($kind, $request->values(), $request->user()?->id);

        $this->toast('success', 'Purpose mapping saved.');

        return back();
    }

    // ============================================================ helpers

    private function canManage(Request $request): bool
    {
        return $request->user()?->can(Permission::ManageClosingMappings->value) ?? false;
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($this->canManage($request), 403);
    }

    private static function outletCode(string $value): string
    {
        return mb_strtoupper(trim($value));
    }

    /**
     * A closing menu's key is its menuId (a UUID); anything else is a 404.
     */
    private static function itemKey(string $source, string $value): string
    {
        abort_if($source === ClosingRecapItem::SOURCE_CLOSING && ! Str::isUuid($value), 404);

        return ClosingItemMapping::keyFor($source, $value);
    }

    private static function dateOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? substr($value, 0, 10) : null;
    }
}
