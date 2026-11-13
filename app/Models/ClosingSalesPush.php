<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The Push Sales Data V2 transaction one prepared version of a closing recap
 * becomes (ADR-032, P29). Sending hands it to a SalesPush (ADR-022).
 *
 * @property int $id
 * @property string $public_id
 * @property int $closing_recap_id
 * @property int $recap_sequence
 * @property string $sales_num
 * @property array{salesHead: array<string, mixed>} $payload
 * @property string $status
 * @property int|null $sales_push_id
 * @property int|null $prepared_by
 * @property Carbon|null $sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ClosingSalesPush extends Model
{
    use HasUlids;

    /** Prepared, not sent. */
    public const STATUS_PREPARED = 'prepared';

    /** A newer preparation replaced it before it was sent. */
    public const STATUS_SUPERSEDED = 'superseded';

    /** Handed to the sales push queue; its SalesPush says what the ESB did. */
    public const STATUS_SENT = 'sent';

    protected $guarded = ['id', 'public_id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recap_sequence' => 'integer',
            'payload' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    /**
     * @return BelongsTo<ClosingRecap, $this>
     */
    public function recap(): BelongsTo
    {
        return $this->belongsTo(ClosingRecap::class, 'closing_recap_id');
    }

    /**
     * @return BelongsTo<SalesPush, $this>
     */
    public function salesPush(): BelongsTo
    {
        return $this->belongsTo(SalesPush::class);
    }
}
