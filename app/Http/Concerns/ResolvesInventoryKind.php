<?php

namespace App\Http\Concerns;

use App\Domain\Canonical\InventoryKind;
use Illuminate\Http\Request;

/**
 * The inventory document a route serves: every inventory route carries its
 * kind as a route default (ADR-026), so each document keeps its own URLs.
 */
trait ResolvesInventoryKind
{
    protected function inventoryKind(Request $request): InventoryKind
    {
        $kind = $request->route('kind');

        return $kind instanceof InventoryKind ? $kind : InventoryKind::from(is_string($kind) ? $kind : '');
    }
}
