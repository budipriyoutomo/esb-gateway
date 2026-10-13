<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * One sales transaction a POS handed the gateway, and what the ESB said about
 * it (ADR-022).
 *
 * @property int $id
 * @property string $public_id
 * @property int|null $api_client_id
 * @property string $sales_num
 * @property string $branch_code
 * @property Carbon|null $sales_date
 * @property array{salesHead: array<string, mixed>} $payload
 * @property string $status
 * @property int $attempts
 * @property Carbon|null $last_attempted_at
 * @property string|null $failure
 * @property string|null $correlation_id
 * @property list<array{field: string|null, message: string}> $errors
 * @property string|null $esb_status
 * @property string|null $esb_sync_date
 * @property Carbon|null $pushed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SalesPush extends Model
{
    use HasUlids;

    /** Waiting for the queue, or between attempts. */
    public const STATUS_QUEUED = 'queued';

    /** The ESB accepted it. */
    public const STATUS_SUCCEEDED = 'succeeded';

    /** The ESB refused it. The POS has to fix the data; repeating it will not help. */
    public const STATUS_REJECTED = 'rejected';

    /** The gateway gave up: attempts ran out, or the ESB setup is broken. */
    public const STATUS_FAILED = 'failed';

    /** A newer push of the same salesNum arrived, so this one is never sent. */
    public const STATUS_SUPERSEDED = 'superseded';

    public const STATUSES = [
        self::STATUS_QUEUED,
        self::STATUS_SUCCEEDED,
        self::STATUS_REJECTED,
        self::STATUS_FAILED,
        self::STATUS_SUPERSEDED,
    ];

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
            'sales_date' => 'date',
            // Customer names and payment details: encrypted at rest.
            'payload' => 'encrypted:array',
            'errors' => 'array',
            'attempts' => 'integer',
            'last_attempted_at' => 'datetime',
            'pushed_at' => 'datetime',
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
     * Store a transaction as received. Queuing it is the caller's job.
     *
     * @param  array{salesHead: array<string, mixed>}  $body  Already validated.
     */
    public static function receive(ApiClient $client, array $body): self
    {
        $head = $body['salesHead'];

        return self::query()->create([
            'api_client_id' => $client->id,
            'sales_num' => (string) $head['salesNum'],
            'branch_code' => (string) $head['branchCode'],
            'sales_date' => self::dateOrNull($head['salesDate'] ?? null),
            'payload' => ['salesHead' => $head],
            'status' => self::STATUS_QUEUED,
        ]);
    }

    /**
     * @return BelongsTo<ApiClient, $this>
     */
    public function apiClient(): BelongsTo
    {
        return $this->belongsTo(ApiClient::class);
    }

    /**
     * Whether a later push of the same salesNum is waiting or already
     * accepted. The ESB keeps whatever arrives last, so this one must not be
     * sent over it. A later push the ESB refused does not count.
     */
    public function hasNewerPush(): bool
    {
        return self::query()
            ->where('sales_num', $this->sales_num)
            ->where('id', '>', $this->id)
            ->whereIn('status', [self::STATUS_QUEUED, self::STATUS_SUCCEEDED])
            ->exists();
    }

    public function canBeRetried(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_REJECTED], true)
            && ! $this->hasNewerPush();
    }

    private static function dateOrNull(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('!Y-m-d', $value) ?: null;
        } catch (Throwable) {
            return null;
        }
    }
}
