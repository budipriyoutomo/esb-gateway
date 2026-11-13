<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One item journal a closing recap becomes in the ERP: one stock kind of one
 * prepared version (ADR-032, P28).
 *
 * @property int $id
 * @property string $public_id
 * @property int $closing_recap_id
 * @property int $recap_sequence
 * @property string $kind
 * @property int $purpose_id
 * @property string $purpose_name
 * @property int $direction
 * @property int $branch_id
 * @property int $location_id
 * @property Carbon $journal_date
 * @property string $notes
 * @property list<array{productDetailId: int, productCode: string|null, productName: string, uomName: string|null, qty: string}> $lines
 * @property list<array<string, mixed>> $breakdown
 * @property string $status
 * @property string|null $item_journal_num
 * @property string|null $failure
 * @property string|null $correlation_id
 * @property list<array{field: string|null, message: string}> $errors
 * @property int|null $prepared_by
 * @property Carbon|null $sent_at
 * @property Carbon|null $deleted_in_erp_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ClosingItemJournal extends Model
{
    use HasUlids;

    /** Prepared, not sent. */
    public const STATUS_PREPARED = 'prepared';

    /** A newer preparation replaced it before it was sent. Never sent. */
    public const STATUS_SUPERSEDED = 'superseded';

    /** In the ERP, under item_journal_num. */
    public const STATUS_CREATED = 'created';

    /** The ERP refused it. It can be sent again. */
    public const STATUS_FAILED = 'failed';

    /** The create's outcome is unknown: someone must check the ERP. */
    public const STATUS_UNCONFIRMED = 'unconfirmed';

    /** Deleted in the ERP because a revision replaced it. */
    public const STATUS_DELETED = 'deleted';

    /** The ERP refused to delete it (TBD: e.g. once authorized). */
    public const STATUS_DELETE_REFUSED = 'delete_refused';

    /** The delete's outcome is unknown and it was still readable. */
    public const STATUS_DELETE_UNCONFIRMED = 'delete_unconfirmed';

    /** Statuses that stop a recap from being prepared or sent until settled. */
    public const BLOCKING = [self::STATUS_UNCONFIRMED, self::STATUS_DELETE_UNCONFIRMED];

    protected $guarded = ['id', 'public_id'];

    protected $attributes = [
        'errors' => '[]',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recap_sequence' => 'integer',
            'purpose_id' => 'integer',
            'direction' => 'integer',
            'branch_id' => 'integer',
            'location_id' => 'integer',
            'journal_date' => 'date',
            'lines' => 'array',
            'breakdown' => 'array',
            'errors' => 'array',
            'sent_at' => 'datetime',
            'deleted_in_erp_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return BelongsTo<ClosingRecap, $this>
     */
    public function recap(): BelongsTo
    {
        return $this->belongsTo(ClosingRecap::class, 'closing_recap_id');
    }

    public function canBeResolved(): bool
    {
        return in_array($this->status, self::BLOCKING, true);
    }
}
