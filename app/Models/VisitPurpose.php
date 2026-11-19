<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A visit purpose (dine in, take away, delivery...), kept in the gateway as
 * the official list of the visitPurposeName values closing recaps carry
 * ([ESB-052]). The ESB's own list is not read: it needs a POS backend
 * credential the gateway does not hold.
 *
 * @property int $id
 * @property string $name
 * @property string $name_key
 * @property string|null $notes
 * @property bool $is_active
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class VisitPurpose extends Model
{
    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * A name as the gateway matches it, the same way the closing recaps do
     * (ClosingRecap::purposeKey): trimmed and upper-cased.
     */
    public static function keyOf(string $name): string
    {
        return ClosingRecap::purposeKey($name);
    }

    /**
     * @return array{id: int, name: string, notes: string|null, active: bool}
     */
    public function toView(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'notes' => $this->notes,
            'active' => $this->is_active,
        ];
    }
}
