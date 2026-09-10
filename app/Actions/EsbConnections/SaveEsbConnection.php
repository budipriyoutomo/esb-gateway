<?php

namespace App\Actions\EsbConnections;

use App\Audit\AuditAction;
use App\Integrations\ESB\Auth\TokenManager;
use App\Integrations\ESB\EsbFamily;
use App\Models\AuditLog;
use App\Models\EsbConnection;
use Illuminate\Support\Facades\DB;

/**
 * Create or replace the runtime override for one ESB family (ADR-018).
 *
 * Three things have to happen together, or the change is worse than not making
 * it: the row is written, the change is audited, and any ESB token minted with
 * the previous credential is discarded.
 */
class SaveEsbConnection
{
    public function __construct(private readonly TokenManager $tokenManager) {}

    /**
     * @param  array{
     *     base_url: string,
     *     auth_mechanism: string,
     *     static_token?: string|null,
     *     username?: string|null,
     *     password?: string|null,
     *     is_active: bool
     * }  $attributes
     */
    public function handle(EsbFamily $family, string $environment, array $attributes): EsbConnection
    {
        $existing = EsbConnection::query()
            ->where('family', $family->value)
            ->where('environment', $environment)
            ->first();

        $credentials = $this->mergeCredentials($existing, $attributes);
        $changed = $this->changedFields($existing, $attributes, $credentials);

        return DB::transaction(function () use ($family, $environment, $attributes, $credentials, $changed, $existing): EsbConnection {
            $connection = EsbConnection::query()->updateOrCreate(
                ['family' => $family->value, 'environment' => $environment],
                [
                    'base_url' => rtrim($attributes['base_url'], '/'),
                    'auth_mechanism' => $attributes['auth_mechanism'],
                    'credentials' => $credentials === [] ? null : $credentials,
                    'is_active' => $attributes['is_active'],
                ],
            );

            // Which fields moved, never what they moved to. A credential must
            // not reach the audit log any more than it reaches the application
            // log (docs/10-security.md).
            AuditLog::record(
                action: AuditAction::EsbConnectionUpdated,
                auditable: $connection,
                metadata: [
                    'family' => $family->value,
                    'environment' => $environment,
                    'created' => $existing === null,
                    'changed' => $changed,
                ],
            );

            if (in_array('credentials', $changed, true)) {
                // A token minted with the previous credential keeps working
                // until it expires, so without this the change would look like
                // it did nothing for up to an hour (ADR-009).
                $this->tokenManager->forget();
            }

            return $connection;
        });
    }

    /**
     * An empty credential field means "leave what is stored".
     *
     * The form is never shown the stored value, so it comes back empty on
     * every later edit. Treating that as "clear it" would wipe the credential
     * each time somebody corrected a base URL.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, string>
     */
    private function mergeCredentials(?EsbConnection $existing, array $attributes): array
    {
        $credentials = $existing !== null ? ($existing->credentials ?? []) : [];

        foreach (['static_token', 'username', 'password'] as $field) {
            $value = $attributes[$field] ?? null;

            if (is_string($value) && trim($value) !== '') {
                $credentials[$field] = $value;
            }
        }

        // Keep only the fields the chosen mechanism actually uses, so
        // switching from login to a static key does not leave a stale password
        // encrypted in the row forever.
        $keep = $attributes['auth_mechanism'] === 'login'
            ? ['username', 'password']
            : ['static_token'];

        return array_filter(
            $credentials,
            static fn (string $key): bool => in_array($key, $keep, true),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, string>  $credentials
     * @return list<string>
     */
    private function changedFields(?EsbConnection $existing, array $attributes, array $credentials): array
    {
        if ($existing === null) {
            return array_values(array_filter([
                'base_url',
                'auth_mechanism',
                $credentials === [] ? null : 'credentials',
                'is_active',
            ]));
        }

        $changed = [];

        if (rtrim($attributes['base_url'], '/') !== $existing->base_url) {
            $changed[] = 'base_url';
        }

        if ($attributes['auth_mechanism'] !== $existing->auth_mechanism) {
            $changed[] = 'auth_mechanism';
        }

        if ($credentials !== ($existing->credentials ?? [])) {
            $changed[] = 'credentials';
        }

        if ($attributes['is_active'] !== $existing->is_active) {
            $changed[] = 'is_active';
        }

        return $changed;
    }
}
