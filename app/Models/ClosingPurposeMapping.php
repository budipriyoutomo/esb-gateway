<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A stock kind of a closing recap -> item journal purpose and direction
 * (ADR-032).
 *
 * @property int $id
 * @property string $kind
 * @property int $purpose_id
 * @property string $purpose_name
 * @property int $direction -1 (stock out) or 1 (stock in)
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ClosingPurposeMapping extends Model
{
    public const KIND_WASTE = 'waste';

    public const KIND_ADJUSTMENT = 'adjustment';

    public const KIND_COMPENSATION = 'compensation';

    /** Each kind => its recap item column, in screen order. */
    public const KINDS = [
        self::KIND_WASTE => 'waste',
        self::KIND_ADJUSTMENT => 'adjustment',
        self::KIND_COMPENSATION => 'compensation',
    ];

    public const DIRECTIONS = [-1, 1];

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purpose_id' => 'integer',
            'direction' => 'integer',
        ];
    }

    public static function label(string $kind): string
    {
        return ucfirst($kind);
    }

    /**
     * @return array{purposeId: int, purposeName: string, direction: int}
     */
    public function toView(): array
    {
        return [
            'purposeId' => $this->purpose_id,
            'purposeName' => $this->purpose_name,
            'direction' => $this->direction,
        ];
    }
}
