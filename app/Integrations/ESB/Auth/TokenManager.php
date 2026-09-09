<?php

namespace App\Integrations\ESB\Auth;

use App\Integrations\ESB\BaseUrlResolver;
use App\Integrations\ESB\ErrorClassifier;
use App\Integrations\ESB\EsbContext;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbAuthenticationException;
use App\Integrations\ESB\Exceptions\EsbConfigurationException;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbMalformedResponseException;
use App\Integrations\ESB\Responses\EsbEnvelope;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Supplies the bearer token for ESB requests.
 *
 * The ESB permits only one active session per credential: "A successful API
 * login will log you out of any existing ESB Core session using the same
 * credentials, vice versa."
 *
 * So the gateway performs at most one login per credential set and shares the
 * result through the cache across every web request and queue worker, with
 * login and refresh serialized behind a lock (ADR-009). Logging in per request
 * would make concurrent operations invalidate each other's tokens.
 *
 * A static API key avoids all of this and is preferred where the ESB team can
 * issue one.
 */
class TokenManager
{
    private const CACHE_KEY_PREFIX = 'esb:token:';

    private const LOCK_KEY_PREFIX = 'esb:token-lock:';

    /**
     * How long the lock is held if the holder dies mid-login.
     */
    private const LOCK_TTL_SECONDS = 30;

    /**
     * How long a caller waits for the lock before giving up.
     *
     * Bounded so that a stuck holder cannot hang every worker in the fleet.
     */
    private const LOCK_WAIT_SECONDS = 15;

    /**
     * Refresh this many seconds before the access token actually expires, so a
     * request in flight does not cross the boundary.
     */
    private const EXPIRY_SKEW_SECONDS = 60;

    public function __construct(
        private readonly Config $config,
        private readonly BaseUrlResolver $baseUrlResolver,
        private readonly ErrorClassifier $errorClassifier,
    ) {}

    /**
     * The bearer token to send on ESB requests.
     */
    public function token(): string
    {
        return match ($this->mechanism()) {
            'static_token' => $this->staticToken(),
            'login' => $this->accessToken(),
            default => throw new EsbConfigurationException(sprintf(
                'Unknown ESB auth mechanism "%s". Expected "static_token" or "login".',
                $this->mechanism(),
            )),
        };
    }

    /**
     * Discard the cached token.
     *
     * Called when the ESB answers EC03100001, which can mean our session was
     * invalidated by a login elsewhere with the same credentials.
     */
    public function forget(): void
    {
        Cache::forget($this->cacheKey());
    }

    /**
     * Store a token pair. Public so tests can arrange cache state.
     *
     * @param  int  $expiresInSeconds  lifetime of the access token
     */
    public function storeTokens(string $accessToken, ?string $refreshToken, int $expiresInSeconds): void
    {
        Cache::put(
            $this->cacheKey(),
            [
                'access' => $accessToken,
                'refresh' => $refreshToken,
                'expires_at' => now()->addSeconds($expiresInSeconds)->getTimestamp(),
            ],
            // Keep the record alive for the refresh token's documented 24-hour
            // lifetime, so an expired access token can still be refreshed
            // rather than forcing a full login.
            now()->addHours(24),
        );
    }

    private function mechanism(): string
    {
        $mechanism = $this->config->get('esb.auth.mechanism');

        return is_string($mechanism) ? $mechanism : '';
    }

    private function staticToken(): string
    {
        $token = $this->config->get('esb.auth.static_token');

        if (! is_string($token) || trim($token) === '') {
            throw new EsbConfigurationException(
                'ESB_AUTH_MECHANISM is "static_token" but ESB_STATIC_TOKEN is not set. '
                .'Obtain an API key from the ESB team, or switch to the login mechanism.',
            );
        }

        return $token;
    }

    private function accessToken(): string
    {
        $cached = $this->cachedTokens();

        if ($cached !== null && $this->accessTokenIsUsable($cached)) {
            return $cached['access'];
        }

        $this->assertCredentialsConfigured();

        $lock = Cache::lock($this->lockKey(), self::LOCK_TTL_SECONDS);

        /** @var string $token */
        $token = $lock->block(self::LOCK_WAIT_SECONDS, function (): string {
            // Read again now that we hold the lock. Another process may have
            // logged in while we queued -- without this second read, every
            // waiter would log in on being granted the lock, and each login
            // would invalidate the one before it.
            $cached = $this->cachedTokens();

            if ($cached !== null && $this->accessTokenIsUsable($cached)) {
                return $cached['access'];
            }

            $refreshToken = $cached['refresh'] ?? null;

            if (is_string($refreshToken) && $refreshToken !== '') {
                $refreshed = $this->tryRefresh($refreshToken);

                if ($refreshed !== null) {
                    return $refreshed;
                }
            }

            return $this->login();
        });

        return $token;
    }

    /**
     * @return array{access: string, refresh: ?string, expires_at: int}|null
     */
    private function cachedTokens(): ?array
    {
        $cached = Cache::get($this->cacheKey());

        if (! is_array($cached) || ! isset($cached['access']) || ! is_string($cached['access'])) {
            return null;
        }

        $refresh = $cached['refresh'] ?? null;

        return [
            'access' => $cached['access'],
            'refresh' => is_string($refresh) ? $refresh : null,
            'expires_at' => is_numeric($cached['expires_at'] ?? null) ? (int) $cached['expires_at'] : 0,
        ];
    }

    /**
     * @param  array{access: string, refresh: ?string, expires_at: int}  $cached
     */
    private function accessTokenIsUsable(array $cached): bool
    {
        return now()->getTimestamp() < $cached['expires_at'] - self::EXPIRY_SKEW_SECONDS;
    }

    /**
     * Exchange the refresh token for a new access token.
     *
     * Returns null when the ESB rejects it, so the caller falls back to a full
     * login rather than failing the request.
     */
    private function tryRefresh(string $refreshToken): ?string
    {
        try {
            $envelope = $this->send(
                'GET',
                '/auth/refresh',
                operation: 'auth.refresh',
                headers: ['Authorization' => 'Bearer '.$refreshToken],
            );
        } catch (EsbException) {
            return null;
        }

        return $this->storeFromEnvelope($envelope);
    }

    private function login(): string
    {
        $envelope = $this->send(
            'POST',
            '/auth/login',
            operation: 'auth.login',
            body: [
                'username' => (string) $this->config->get('esb.auth.username'),
                'password' => (string) $this->config->get('esb.auth.password'),
            ],
        );

        $token = $this->storeFromEnvelope($envelope);

        if ($token === null) {
            throw new EsbMalformedResponseException(
                context: new EsbContext(correlationId: (string) Str::uuid(), operation: 'auth.login'),
                esbCode: $envelope->code,
                upstreamMessage: 'Login succeeded but the response carried no accessToken.',
            );
        }

        return $token;
    }

    private function storeFromEnvelope(EsbEnvelope $envelope): ?string
    {
        $result = $envelope->resultArray();

        $access = $result['accessToken'] ?? null;
        $refresh = $result['refreshToken'] ?? null;

        if (! is_string($access) || $access === '') {
            return null;
        }

        // The vendor documents the access token as expiring after one hour. The
        // JWT's own `exp` is treated as advisory only -- the ESB is the
        // authority, and an EC03100001 is handled whatever the clock says.
        $this->storeTokens($access, is_string($refresh) ? $refresh : null, 3600);

        return $access;
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $body
     */
    private function send(string $method, string $path, string $operation, array $headers = [], array $body = []): EsbEnvelope
    {
        $context = new EsbContext(
            correlationId: (string) Str::uuid(),
            operation: $operation,
            family: $this->authFamily(),
            method: $method,
            path: $path,
        );

        $url = $this->baseUrlResolver->resolve($this->authFamily()).$path;

        try {
            $response = Http::withHeaders($headers)
                ->acceptJson()
                ->connectTimeout((int) $this->config->get('esb.timeouts.connect', 5))
                ->timeout((int) $this->config->get('esb.timeouts.request', 30))
                ->send($method, $url, $body === [] ? [] : ['json' => $body]);
        } catch (Throwable $exception) {
            throw new EsbAuthenticationException(
                context: $context,
                upstreamMessage: 'Could not reach the ESB authentication endpoint.',
                previous: $exception,
            );
        }

        $decoded = $response->json();

        $envelope = is_array($decoded) ? EsbEnvelope::tryFromArray($decoded) : null;

        if ($envelope === null) {
            throw new EsbMalformedResponseException(
                context: $context->withTiming(0, $response->status()),
                upstreamMessage: 'Authentication response was not a recognisable ESB envelope.',
            );
        }

        if (! $envelope->isSuccessful()) {
            $classified = $this->errorClassifier->classify($envelope, $context->withTiming(0, $response->status()));

            // Any failure to authenticate is an authentication failure to the
            // caller, whatever code the ESB used to express it. The original
            // classification is preserved as the previous exception.
            throw $classified instanceof EsbAuthenticationException
                ? $classified
                : new EsbAuthenticationException(
                    context: $context,
                    esbCode: $envelope->code,
                    upstreamMessage: $envelope->message,
                    previous: $classified,
                );
        }

        return $envelope;
    }

    private function authFamily(): EsbFamily
    {
        $family = $this->config->get('esb.auth.family');

        return (is_string($family) ? EsbFamily::tryFrom($family) : null) ?? EsbFamily::Core;
    }

    private function assertCredentialsConfigured(): void
    {
        $username = $this->config->get('esb.auth.username');
        $password = $this->config->get('esb.auth.password');

        if (! is_string($username) || trim($username) === '' || ! is_string($password) || $password === '') {
            throw new EsbConfigurationException(
                'ESB_AUTH_MECHANISM is "login" but ESB_USERNAME or ESB_PASSWORD is not set. '
                .'Use dedicated integration credentials, never a person\'s ESB Core account -- '
                .'a human signing in with the same credentials would invalidate the gateway session.',
            );
        }
    }

    private function cacheKey(): string
    {
        return self::CACHE_KEY_PREFIX.$this->config->get('esb.environment');
    }

    private function lockKey(): string
    {
        return self::LOCK_KEY_PREFIX.$this->config->get('esb.environment');
    }
}
