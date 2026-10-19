<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One uploaded document template spreadsheet, and the templates read from it
 * (ADR-023).
 *
 * @property int $id
 * @property int|null $uploaded_by
 * @property string $filename
 * @property string $file_sha256
 * @property string $status
 * @property list<string> $problems
 * @property string|null $last_error
 * @property Carbon|null $validated_at
 * @property Carbon|null $synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DocumentTemplateImport extends Model
{
    /** Nothing running; validate and sync may start. */
    public const STATUS_IDLE = 'idle';

    /** A validate job holds the import. */
    public const STATUS_VALIDATING = 'validating';

    /** A sync job holds the import. */
    public const STATUS_SYNCING = 'syncing';

    protected $guarded = ['id'];

    protected $attributes = [
        'status' => self::STATUS_IDLE,
        'problems' => '[]',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'problems' => 'array',
            'validated_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    public function isBusy(): bool
    {
        return $this->status !== self::STATUS_IDLE;
    }

    /**
     * Take the import for a job: only one validate or sync runs at a time,
     * so two clicks cannot send a template twice.
     *
     * @return bool false when another job already holds it
     */
    public function claim(string $status): bool
    {
        $claimed = self::query()
            ->whereKey($this->getKey())
            ->where('status', self::STATUS_IDLE)
            ->update(['status' => $status, 'last_error' => null, 'updated_at' => now()]) === 1;

        if ($claimed) {
            $this->refresh();
        }

        return $claimed;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function release(array $attributes = []): void
    {
        $this->update(['status' => self::STATUS_IDLE, ...$attributes]);
    }

    /**
     * @return HasMany<DocumentTemplateImportEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(DocumentTemplateImportEntry::class)->orderBy('position');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
