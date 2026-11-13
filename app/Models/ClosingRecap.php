<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * The current closing recap of one outlet on one production date (ADR-032).
 *
 * A newer sequence replaces its items in full. Preparing and sending it to the
 * ESB are later steps, which add their own statuses.
 *
 * @property int $id
 * @property string $public_id
 * @property string $outlet_code
 * @property Carbon $production_date
 * @property int $sequence
 * @property int|null $closing_submission_id
 * @property string $status
 * @property array<string, mixed>|null $sales Revision 3 header totals (TBD).
 * @property list<array<string, mixed>>|null $payments Revision 3 payments (TBD).
 * @property Carbon|null $last_received_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ClosingRecap extends Model
{
    use HasUlids;

    /** Held in the gateway, not yet sent to the ESB. */
    public const STATUS_RECEIVED = 'received';

    /** Its documents are prepared from the current version, not yet sent. */
    public const STATUS_PREPARED = 'prepared';

    /** Every document of the current version is in the ERP. */
    public const STATUS_SENT = 'sent';

    /** A send left something refused, failed or unconfirmed. */
    public const STATUS_ATTENTION = 'attention';

    /** A newer sequence arrived after it was prepared or sent. */
    public const STATUS_REVISED = 'revised';

    public const STATUSES = [
        self::STATUS_RECEIVED,
        self::STATUS_PREPARED,
        self::STATUS_SENT,
        self::STATUS_ATTENTION,
        self::STATUS_REVISED,
    ];

    /** Header totals of contract revision 3 (TBD), forwarded to Push Sales as given. */
    public const SALES_TOTALS = [
        'paxTotal', 'flagInclusive', 'subtotal', 'discountTotal', 'menuDiscountTotal',
        'promotionDiscount', 'otherTaxTotal', 'vatTotal', 'otherVatTotal', 'deliveryFee',
        'orderFee', 'voucherTotal', 'roundingTotal', 'grandTotal', 'paymentTotal',
    ];

    protected $guarded = ['id', 'public_id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'sequence' => 'integer',
            'last_received_at' => 'datetime',
            'sales' => 'array',
            'payments' => 'array',
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
     * The submission the current items came from.
     *
     * @return BelongsTo<ClosingSubmission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(ClosingSubmission::class, 'closing_submission_id');
    }

    /**
     * @return HasMany<ClosingRecapItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ClosingRecapItem::class);
    }

    /**
     * @return HasMany<ClosingSalesPush, $this>
     */
    public function salesPushes(): HasMany
    {
        return $this->hasMany(ClosingSalesPush::class);
    }

    /**
     * Whether sync-api sent the prices, taxes and payments Push Sales needs
     * (contract revision 3, TBD).
     */
    public function hasSalesData(): bool
    {
        return $this->sales !== null && $this->payments !== null;
    }

    /**
     * @return HasMany<ClosingItemJournal, $this>
     */
    public function itemJournals(): HasMany
    {
        return $this->hasMany(ClosingItemJournal::class);
    }

    /**
     * Whether a journal's ERP outcome is still unknown. Nothing more is
     * prepared or sent until someone settles it.
     */
    public function hasUnsettledJournal(): bool
    {
        return $this->itemJournals()->whereIn('status', ClosingItemJournal::BLOCKING)->exists();
    }

    public function hasCreatedJournalForCurrentVersion(): bool
    {
        return $this->itemJournals()
            ->where('recap_sequence', $this->sequence)
            ->where('status', ClosingItemJournal::STATUS_CREATED)
            ->exists();
    }

    /**
     * Whether the current version has journals waiting to be sent.
     */
    public function hasJournalsToSend(): bool
    {
        return $this->itemJournals()
            ->where('recap_sequence', $this->sequence)
            ->whereIn('status', [ClosingItemJournal::STATUS_PREPARED, ClosingItemJournal::STATUS_FAILED])
            ->exists();
    }
}
