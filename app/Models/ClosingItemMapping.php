<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A closing menu or POS product -> ERP menu and bill of material (ADR-032).
 *
 * The key is the item's menuId (closing, lower-cased UUID) or productCode
 * (pos_only, upper-cased): see self::keyFor().
 *
 * @property int $id
 * @property string $source
 * @property string $item_key
 * @property int|null $esb_menu_id
 * @property string|null $esb_menu_code
 * @property string|null $esb_menu_name
 * @property int|null $bom_id
 * @property string|null $bom_name
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ClosingItemMapping extends Model
{
    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'esb_menu_id' => 'integer',
            'bom_id' => 'integer',
        ];
    }

    /**
     * The mapping key of a recap item, or of a key typed in a URL.
     */
    public static function keyFor(string $source, string $value): string
    {
        $value = trim($value);

        return $source === ClosingRecapItem::SOURCE_CLOSING ? strtolower($value) : mb_strtoupper($value);
    }

    public static function keyOf(ClosingRecapItem $item): string
    {
        return self::keyFor(
            $item->source,
            (string) ($item->source === ClosingRecapItem::SOURCE_CLOSING ? $item->menu_id : $item->product_code),
        );
    }

    public function menuLabel(): ?string
    {
        if ($this->esb_menu_id === null) {
            return null;
        }

        return $this->esb_menu_code !== null && $this->esb_menu_code !== ''
            ? $this->esb_menu_code.' · '.$this->esb_menu_name
            : (string) $this->esb_menu_name;
    }

    /**
     * @return array{esbMenuId: int|null, esbMenuCode: string|null, esbMenuName: string|null, bomId: int|null, bomName: string|null}
     */
    public function toView(): array
    {
        return [
            'esbMenuId' => $this->esb_menu_id,
            'esbMenuCode' => $this->esb_menu_code,
            'esbMenuName' => $this->esb_menu_name,
            'bomId' => $this->bom_id,
            'bomName' => $this->bom_name,
        ];
    }
}
