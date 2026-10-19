<?php

namespace App\Models;

use App\Domain\Canonical\DocumentTemplateType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One template of an imported spreadsheet, and where it stands with the ERP
 * (ADR-023).
 *
 * @property int $id
 * @property int $document_template_import_id
 * @property int $position
 * @property string $name
 * @property int|null $type
 * @property list<string> $branch_codes
 * @property list<int>|null $branch_ids
 * @property string $status
 * @property list<string> $problems
 * @property int|null $esb_template_id
 * @property string|null $correlation_id
 * @property Carbon|null $synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DocumentTemplateImportEntry extends Model
{
    /** The sheet itself is wrong for it; fix the file and upload again. Final. */
    public const STATUS_INVALID = 'invalid';

    /** Read from the sheet, not yet checked against the ERP. */
    public const STATUS_PENDING = 'pending';

    /** Checked: every branch and product resolved, name free. Sync sends it. */
    public const STATUS_READY = 'ready';

    /** Checked: something the ERP does not have, or the name is taken. */
    public const STATUS_BLOCKED = 'blocked';

    /** Being sent right now. */
    public const STATUS_SYNCING = 'syncing';

    /** The ERP has it; esb_template_id is its id when known. */
    public const STATUS_SYNCED = 'synced';

    /** The ERP refused it; the problems say why. */
    public const STATUS_FAILED = 'failed';

    /** Sent, but the ERP gave no definite answer. Validate again to find out. */
    public const STATUS_UNCONFIRMED = 'unconfirmed';

    public const STATUSES = [
        self::STATUS_INVALID,
        self::STATUS_PENDING,
        self::STATUS_READY,
        self::STATUS_BLOCKED,
        self::STATUS_SYNCING,
        self::STATUS_SYNCED,
        self::STATUS_FAILED,
        self::STATUS_UNCONFIRMED,
    ];

    /**
     * What validate looks at again. Invalid entries need a new file; synced
     * and syncing ones are past checking.
     */
    public const CHECKABLE = [
        self::STATUS_PENDING,
        self::STATUS_READY,
        self::STATUS_BLOCKED,
        self::STATUS_FAILED,
        self::STATUS_UNCONFIRMED,
    ];

    protected $guarded = ['id'];

    protected $attributes = [
        'problems' => '[]',
        'branch_codes' => '[]',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'type' => 'integer',
            'branch_codes' => 'array',
            'branch_ids' => 'array',
            'problems' => 'array',
            'esb_template_id' => 'integer',
            'synced_at' => 'datetime',
        ];
    }

    public function documentTemplateType(): ?DocumentTemplateType
    {
        return $this->type === null ? null : DocumentTemplateType::tryFrom($this->type);
    }

    /**
     * @return BelongsTo<DocumentTemplateImport, $this>
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplateImport::class, 'document_template_import_id');
    }

    /**
     * @return HasMany<DocumentTemplateImportLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(DocumentTemplateImportLine::class)->orderBy('row_number');
    }
}
