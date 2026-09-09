<?php

namespace App\Models;

use App\Audit\AuditAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use RuntimeException;

/**
 * An immutable record of a privileged change.
 *
 * @property int $id
 * @property int|null $actor_id
 * @property string $action
 * @property string|null $auditable_type
 * @property int|null $auditable_id
 * @property array<string, mixed> $metadata
 * @property string|null $ip_address
 * @property Carbon|null $created_at
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // An audit trail that can be edited is not an audit trail. Enforcing
        // this in the model means a stray update() in future code fails loudly
        // instead of quietly rewriting history.
        static::updating(function (): never {
            throw new RuntimeException('Audit log entries are immutable and cannot be updated.');
        });

        static::deleting(function (): never {
            throw new RuntimeException('Audit log entries are immutable and cannot be deleted.');
        });
    }

    /**
     * Record a privileged change.
     *
     * The actor defaults to the authenticated user. Pass metadata that
     * describes the change itself -- never credentials, tokens, or full
     * payloads (docs/10-security.md).
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function record(
        AuditAction $action,
        ?Model $auditable = null,
        ?int $actorId = null,
        array $metadata = [],
    ): self {
        return self::query()->create([
            'actor_id' => $actorId ?? Auth::id(),
            'action' => $action->value,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'metadata' => $metadata,
            'ip_address' => Request::ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
