<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One product row of an imported template (ADR-023).
 *
 * @property int $id
 * @property int $document_template_import_entry_id
 * @property int $row_number
 * @property string $product_code
 * @property string $product_name
 * @property string $unit
 * @property string $category
 * @property int|null $product_detail_id
 */
class DocumentTemplateImportLine extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'row_number' => 'integer',
            'product_detail_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<DocumentTemplateImportEntry, $this>
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplateImportEntry::class, 'document_template_import_entry_id');
    }
}
