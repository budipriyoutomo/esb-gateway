<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One closing recap request sync-api Maharasa sent and the gateway accepted
 * (ADR-032). Kept for every sequence, with the body exactly as received.
 *
 * @property int $id
 * @property string $public_id
 * @property int|null $api_client_id
 * @property string $message_id
 * @property string $body_hash
 * @property string $outlet_code
 * @property Carbon $production_date
 * @property int $sequence
 * @property Carbon $sent_at
 * @property string $body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ClosingSubmission extends Model
{
    use HasUlids;

    protected $guarded = ['id', 'public_id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'sequence' => 'integer',
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

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return BelongsTo<ApiClient, $this>
     */
    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }
}
