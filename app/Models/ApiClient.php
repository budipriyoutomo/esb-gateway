<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A system allowed to call the gateway's /api routes, such as a POS (ADR-022).
 *
 * @property int $id
 * @property string $name
 * @property string $token_hash
 * @property bool $is_active
 * @property Carbon|null $last_used_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ApiClient extends Model
{
    /**
     * Seconds between last_used_at writes, so a busy client does not turn
     * every request into an UPDATE.
     */
    private const LAST_USED_RESOLUTION_SECONDS = 60;

    protected $fillable = ['name', 'token_hash', 'is_active', 'last_used_at'];

    protected $hidden = ['token_hash'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * Create a client and its token.
     *
     * The token is returned here and nowhere else: only its hash is stored.
     *
     * @return array{self, string}
     */
    public static function issue(string $name): array
    {
        $token = 'egw_'.Str::random(48);

        $client = self::query()->create([
            'name' => $name,
            'token_hash' => self::hash($token),
            'is_active' => true,
        ]);

        return [$client, $token];
    }

    public static function findActiveByToken(string $token): ?self
    {
        if ($token === '') {
            return null;
        }

        return self::query()
            ->where('token_hash', self::hash($token))
            ->where('is_active', true)
            ->first();
    }

    public function markUsed(): void
    {
        if ($this->last_used_at?->gt(now()->subSeconds(self::LAST_USED_RESOLUTION_SECONDS))) {
            return;
        }

        $this->forceFill(['last_used_at' => now()])->saveQuietly();
    }

    /**
     * A plain SHA-256 is enough: the token is 48 random characters, not a
     * password, so there is nothing for a slow hash to protect against.
     */
    private static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
