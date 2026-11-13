<?php

namespace App\Actions\ClosingRecaps;

use App\Audit\AuditAction;
use App\Domain\Canonical\BillOfMaterial;
use App\Domain\Canonical\BillOfMaterialLine;
use App\Integrations\Contracts\BillOfMaterialSource;
use App\Integrations\ESB\Exceptions\EsbNotFoundException;
use App\Models\AuditLog;
use App\Models\ClosingItemJournal;
use App\Models\ClosingItemMapping;
use App\Models\ClosingPurposeMapping;
use App\Models\ClosingRecap;
use App\Models\ClosingRecapItem;
use App\Support\Decimal;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

/**
 * Turn a closing recap's waste, adjustment and compensation into item journal
 * drafts (ADR-032, P28): one per kind with a non-zero line, each closing menu
 * expanded through its BOM one level deep, summed per product detail.
 *
 * The BOMs are read from the ERP now, not from the mapping's copy, and what
 * was read is kept with the draft. Unsent drafts of an earlier preparation
 * are superseded.
 */
class PrepareClosingItemJournals
{
    public function __construct(private readonly BillOfMaterialSource $boms) {}

    /**
     * @return list<ClosingItemJournal> The new drafts.
     *
     * @throws ClosingRecapNotReady
     */
    public function handle(ClosingRecap $recap, ?int $userId): array
    {
        $recap->load(['items' => fn ($query) => $query->orderBy('id')]);
        $mappings = ClosingMappingSet::load();

        $missing = $mappings->missingFor($recap);

        if ($missing !== []) {
            throw new ClosingRecapNotReady($missing[0]['message'].($missing === [$missing[0]] ? '' : ' (and '.(count($missing) - 1).' more)'));
        }

        if ($recap->hasUnsettledJournal()) {
            throw new ClosingRecapNotReady('An item journal of this recap has an unknown outcome in the ERP. Settle it first.');
        }

        // Preparing this version again would put its journals in the ERP twice.
        if ($recap->hasCreatedJournalForCurrentVersion()) {
            throw new ClosingRecapNotReady('This version already has item journals in the ERP. A failed one is sent again with Send; a revision from sync-api can be prepared anew.');
        }

        $outlet = $mappings->outlet($recap->outlet_code);
        assert($outlet !== null); // missingFor() checked it.

        $boms = $this->readBoms($recap, $mappings);

        $drafts = [];

        foreach (ClosingPurposeMapping::KINDS as $kind => $column) {
            $purpose = $mappings->purpose($kind);

            // No purpose means no item of this kind is non-zero (missingFor()).
            if ($purpose === null) {
                continue;
            }

            $built = $this->build($recap, $mappings, $boms, $column, $purpose->direction);

            if ($built['lines'] === []) {
                continue;
            }

            $drafts[] = [
                'closing_recap_id' => $recap->id,
                'recap_sequence' => $recap->sequence,
                'kind' => $kind,
                'purpose_id' => $purpose->purpose_id,
                'purpose_name' => $purpose->purpose_name,
                'direction' => $purpose->direction,
                'branch_id' => $outlet->branch_id,
                'location_id' => $outlet->location_id,
                'journal_date' => $recap->production_date->toDateString(),
                'notes' => sprintf(
                    'Closing %s %s, sequence %d: %s',
                    $recap->outlet_code,
                    $recap->production_date->toDateString(),
                    $recap->sequence,
                    ClosingPurposeMapping::label($kind),
                ),
                'lines' => $built['lines'],
                'breakdown' => $built['breakdown'],
                'status' => ClosingItemJournal::STATUS_PREPARED,
                'prepared_by' => $userId,
            ];
        }

        return DB::transaction(function () use ($recap, $drafts): array {
            $recap->itemJournals()
                ->where('status', ClosingItemJournal::STATUS_PREPARED)
                ->update(['status' => ClosingItemJournal::STATUS_SUPERSEDED]);

            $created = array_map(
                fn (array $draft): ClosingItemJournal => ClosingItemJournal::query()->create($draft),
                $drafts,
            );

            // A recap that was sent or revised keeps its earlier journals in
            // the ERP until the send deletes them.
            $recap->update(['status' => ClosingRecap::STATUS_PREPARED]);

            AuditLog::record(
                action: AuditAction::ClosingRecapPrepared,
                auditable: $recap,
                metadata: [
                    'sequence' => $recap->sequence,
                    'journals' => array_map(fn (ClosingItemJournal $j): array => [
                        'kind' => $j->kind,
                        'lines' => count($j->lines),
                    ], $created),
                ],
            );

            return $created;
        });
    }

    /**
     * Each BOM the recap's menus use, read once.
     *
     * @return array<int, BillOfMaterial>
     *
     * @throws ClosingRecapNotReady
     */
    private function readBoms(ClosingRecap $recap, ClosingMappingSet $mappings): array
    {
        $boms = [];

        foreach ($recap->items as $item) {
            $mapping = $mappings->item($item);

            if ($item->source !== ClosingRecapItem::SOURCE_CLOSING || $mapping?->bom_id === null || isset($boms[$mapping->bom_id])) {
                continue;
            }

            try {
                $bom = $this->boms->findBillOfMaterial($mapping->bom_id);
            } catch (EsbNotFoundException) {
                $bom = null;
            }

            if ($bom === null) {
                throw new ClosingRecapNotReady(sprintf('The ERP has no bill of material %d (%s).', $mapping->bom_id, $mapping->bom_name));
            }

            $boms[$mapping->bom_id] = $bom;
        }

        return $boms;
    }

    /**
     * The lines of one kind, summed per product detail in order of first
     * appearance, and what each menu contributed.
     *
     * @param  array<int, BillOfMaterial>  $boms
     * @return array{lines: list<array<string, mixed>>, breakdown: list<array<string, mixed>>}
     */
    private function build(ClosingRecap $recap, ClosingMappingSet $mappings, array $boms, string $column, int $direction): array
    {
        /** @var array<int, array{line: BillOfMaterialLine, qty: BigDecimal}> $totals */
        $totals = [];
        $breakdown = [];

        foreach ($recap->items as $item) {
            $quantity = Decimal::of((string) $item->getAttribute($column));
            $bomId = $mappings->item($item)?->bom_id;

            if ($item->source !== ClosingRecapItem::SOURCE_CLOSING || $quantity->isZero() || $bomId === null) {
                continue;
            }

            $bom = $boms[$bomId];
            $signed = $quantity->multipliedBy($direction);
            $materials = [];

            foreach ($bom->lines as $line) {
                $perUnit = self::perUnit($line);
                $qty = $signed->multipliedBy($perUnit);

                $totals[$line->productDetailId] ??= ['line' => $line, 'qty' => BigDecimal::zero()];
                $totals[$line->productDetailId]['qty'] = $totals[$line->productDetailId]['qty']->plus($qty);

                $materials[] = [
                    'productDetailId' => $line->productDetailId,
                    'productName' => $line->productName,
                    'uomName' => $line->uomName,
                    'perUnit' => Decimal::trim($perUnit),
                    'qty' => Decimal::trim($qty),
                ];
            }

            $breakdown[] = [
                'item' => ClosingMappingSet::itemLabel($item),
                'itemKey' => ClosingItemMapping::keyOf($item),
                'quantity' => Decimal::trim($quantity),
                'bomId' => $bom->id,
                'bomName' => $bom->name,
                'materials' => $materials,
            ];
        }

        $lines = [];

        foreach ($totals as $productDetailId => $total) {
            $qty = Decimal::round($total['qty']);

            if ($qty === '0') {
                continue;
            }

            $lines[] = [
                'productDetailId' => $productDetailId,
                'productCode' => $total['line']->productCode,
                'productName' => $total['line']->productName,
                'uomName' => $total['line']->uomName,
                'qty' => $qty,
            ];
        }

        return ['lines' => $lines, 'breakdown' => $lines === [] ? [] : $breakdown];
    }

    /**
     * A material's quantity for one unit of the menu.
     *
     * TBD (ADR-032 Open): read as the BOM line's `qty`, taken to be per one
     * unit of the BOM's product and in the material's stock unit. Whether a
     * BOM is per portion or per batch, and whether `uomQty` / `convertionQty`
     * must be applied, is to be checked against real BOMs on staging.
     */
    private static function perUnit(BillOfMaterialLine $line): BigDecimal
    {
        return Decimal::of($line->qty ?? 0);
    }
}
