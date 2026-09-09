<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One ESB call, recorded for troubleshooting.
 *
 * Holds metadata only. Request and response payloads are deliberately absent:
 * they carry personal and financial data, and retention for this table is
 * still TBD (docs/11-logging-monitoring.md).
 *
 * @property int $id
 * @property string $correlation_id
 * @property string $operation
 * @property string $family
 * @property string $environment
 * @property string $method
 * @property string $path
 * @property string $outcome
 * @property string|null $esb_code
 * @property string|null $safe_error_message
 * @property int|null $upstream_status
 * @property int|null $duration_ms
 * @property int $retry_count
 * @property int|null $user_id
 * @property Carbon|null $started_at
 */
class IntegrationLog extends Model
{
    public const OUTCOME_SUCCESS = 'success';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'upstream_status' => 'integer',
            'duration_ms' => 'integer',
            'retry_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function succeeded(): bool
    {
        return $this->outcome === self::OUTCOME_SUCCESS;
    }
}
