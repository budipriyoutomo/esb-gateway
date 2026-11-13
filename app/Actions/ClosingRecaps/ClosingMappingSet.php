<?php

namespace App\Actions\ClosingRecaps;

use App\Models\ClosingItemMapping;
use App\Models\ClosingOutletMapping;
use App\Models\ClosingPurposeMapping;
use App\Models\ClosingRecap;
use App\Models\ClosingRecapItem;
use Brick\Math\BigDecimal;

/**
 * Every closing mapping, loaded once (ADR-032). The tables hold tens to a few
 * hundred rows, so a list page checks all its recaps against one load.
 */
final class ClosingMappingSet
{
    /**
     * @param  array<string, ClosingOutletMapping>  $outlets  By outlet code.
     * @param  array<string, ClosingItemMapping>  $items  By "source|key".
     * @param  array<string, ClosingPurposeMapping>  $purposes  By kind.
     */
    private function __construct(
        private readonly array $outlets,
        private readonly array $items,
        private readonly array $purposes,
    ) {}

    public static function load(): self
    {
        return new self(
            ClosingOutletMapping::query()->get()->keyBy('outlet_code')->all(),
            ClosingItemMapping::query()->get()
                ->keyBy(fn (ClosingItemMapping $m): string => $m->source.'|'.$m->item_key)
                ->all(),
            ClosingPurposeMapping::query()->get()->keyBy('kind')->all(),
        );
    }

    public function outlet(string $outletCode): ?ClosingOutletMapping
    {
        return $this->outlets[$outletCode] ?? null;
    }

    public function item(ClosingRecapItem $item): ?ClosingItemMapping
    {
        return $this->items[$item->source.'|'.ClosingItemMapping::keyOf($item)] ?? null;
    }

    public function purpose(string $kind): ?ClosingPurposeMapping
    {
        return $this->purposes[$kind] ?? null;
    }

    /**
     * What the recap still needs before it can be prepared, in screen order:
     * the outlet, then each item (its sales menu, then its BOM), then each
     * stock kind with a non-zero quantity. A zero quantity needs nothing.
     *
     * @return list<array{type: string, key: string, label: string, message: string}>
     */
    public function missingFor(ClosingRecap $recap): array
    {
        $missing = [];

        if ($this->outlet($recap->outlet_code) === null) {
            $missing[] = self::gap('outlet', $recap->outlet_code, $recap->outlet_code, "Outlet {$recap->outlet_code} has no ERP branch and location.");
        }

        $kindsUsed = [];

        foreach ($recap->items as $item) {
            $mapping = $this->item($item);
            $key = ClosingItemMapping::keyOf($item);
            $label = self::itemLabel($item);

            if (! self::isZero($item->total_sold) && $mapping?->esb_menu_id === null) {
                $missing[] = self::gap('item-menu', $key, $label, "{$label} has no ERP menu for sales.");
            }

            if ($item->source !== ClosingRecapItem::SOURCE_CLOSING) {
                continue;
            }

            $stockKinds = array_filter(
                ClosingPurposeMapping::KINDS,
                fn (string $column): bool => ! self::isZero((string) $item->getAttribute($column)),
            );

            if ($stockKinds !== [] && $mapping?->bom_id === null) {
                $missing[] = self::gap('item-bom', $key, $label, "{$label} has no bill of material for the item journals.");
            }

            $kindsUsed += $stockKinds;
        }

        foreach (array_keys(ClosingPurposeMapping::KINDS) as $kind) {
            if (isset($kindsUsed[$kind]) && $this->purpose($kind) === null) {
                $label = ClosingPurposeMapping::label($kind);
                $missing[] = self::gap('purpose', $kind, $label, "{$label} has no ERP purpose.");
            }
        }

        return $missing;
    }

    public static function itemLabel(ClosingRecapItem $item): string
    {
        $name = $item->source === ClosingRecapItem::SOURCE_CLOSING ? $item->menu_name : $item->product_name;
        $code = $item->source === ClosingRecapItem::SOURCE_CLOSING ? $item->menu_code : $item->product_code;

        return $name ?? $code ?? ClosingItemMapping::keyOf($item);
    }

    public static function isZero(string $quantity): bool
    {
        return BigDecimal::of($quantity)->isZero();
    }

    /**
     * @return array{type: string, key: string, label: string, message: string}
     */
    private static function gap(string $type, string $key, string $label, string $message): array
    {
        return ['type' => $type, 'key' => $key, 'label' => $label, 'message' => $message];
    }
}
