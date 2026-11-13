<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One menu (`closing`) or POS product (`pos_only`) of a closing recap's
 * current version (ADR-032). Quantities are decimal strings, never floats.
 *
 * @property int $id
 * @property int $closing_recap_id
 * @property string $source
 * @property string|null $menu_id
 * @property string|null $menu_code
 * @property string|null $menu_name
 * @property string|null $product_id
 * @property string|null $product_code
 * @property string|null $product_name
 * @property string $colorplate_sold
 * @property string $pos_qty
 * @property string $total_sold
 * @property string $waste
 * @property string $adjustment
 * @property string $compensation
 * @property list<array<string, mixed>>|null $prices Revision 3 price lines (TBD).
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ClosingRecapItem extends Model
{
    public const SOURCE_CLOSING = 'closing';

    public const SOURCE_POS_ONLY = 'pos_only';

    public const SOURCES = [self::SOURCE_CLOSING, self::SOURCE_POS_ONLY];

    /** The quantity fields, as the sender names them => column. */
    public const QUANTITIES = [
        'colorplateSold' => 'colorplate_sold',
        'posQty' => 'pos_qty',
        'totalSold' => 'total_sold',
        'waste' => 'waste',
        'adjustment' => 'adjustment',
        'compensation' => 'compensation',
    ];

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'colorplate_sold' => 'decimal:6',
            'pos_qty' => 'decimal:6',
            'total_sold' => 'decimal:6',
            'waste' => 'decimal:6',
            'adjustment' => 'decimal:6',
            'compensation' => 'decimal:6',
            'prices' => 'array',
        ];
    }

    /**
     * @return BelongsTo<ClosingRecap, $this>
     */
    public function recap(): BelongsTo
    {
        return $this->belongsTo(ClosingRecap::class, 'closing_recap_id');
    }
}
