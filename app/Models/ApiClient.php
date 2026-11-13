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
 * @property list<string>|null $scopes
 * @property bool $is_active
 * @property Carbon|null $last_used_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ApiClient extends Model
{
    /** POST /api/v1/sales-data (ADR-022). */
    public const SCOPE_SALES_DATA = 'sales-data';

    /** POST /api/v1/closing-totals (ADR-032). */
    public const SCOPE_CLOSING_TOTALS = 'closing-totals';

    public const SCOPES = [self::SCOPE_SALES_DATA, self::SCOPE_CLOSING_TOTALS];

    /**
     * Seconds between last_used_at writes, so a busy client does not turn
     * every request into an UPDATE.
     */
    private const LAST_USED_RESOLUTION_SECONDS = 60;

    protected $fillable = ['name', 'token_hash', 'scopes', 'is_active', 'last_used_at'];

    protected $hidden = ['token_hash'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * Create a client and its token.
     *
     * The token is returned here and nowhere else: only its hash is stored.
     *
     * @param  list<string>  $scopes  Each one of self::SCOPES.
     * @return array{self, string}
     */
    public static function issue(string $name, array $scopes = [self::SCOPE_SALES_DATA]): array
    {
        $unknown = array_diff($scopes, self::SCOPES);

        if ($scopes === [] || $unknown !== []) {
            throw new \InvalidArgumentException('Unknown API client scope: '.implode(', ', $unknown));
        }

        $token = 'egw_'.Str::random(48);

        $client = self::query()->create([
            'name' => $name,
            'token_hash' => self::hash($token),
            'scopes' => array_values(array_unique($scopes)),
            'is_active' => true,
        ]);

        return [$client, $token];
    }

    /**
     * The endpoints this client may call. A client issued before scopes
     * existed is a POS and keeps sales data only.
     *
     * @return list<string>
     */
    public function scopes(): array
    {
        return $this->scopes ?? [self::SCOPE_SALES_DATA];
    }

    public function allows(string $scope): bool
    {
        return in_array($scope, $this->scopes(), true);
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
