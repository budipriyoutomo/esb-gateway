<?php

namespace App\Integrations\ESB\Clients;

use App\Integrations\ESB\Auth\TokenManager;
use App\Integrations\ESB\ErrorClassifier;
use App\Integrations\ESB\EsbConnectionResolver;
use App\Integrations\ESB\EsbContext;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbConnectionException;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbMalformedResponseException;
use App\Integrations\ESB\Exceptions\EsbTimeoutException;
use App\Integrations\ESB\Exceptions\EsbUpstreamException;
use App\Integrations\ESB\Responses\EsbEnvelope;
use App\Models\IntegrationLog;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * The single path from this application to the ERP ESB (ADR-006).
 *
 * Responsible for base URL resolution per family, bearer authentication,
 * timeouts, retry policy, error normalization, and recording each call. It
 * knows nothing about any particular ERP operation -- adapters built on top of
 * it own the request and response mapping.
 */
class EsbClient
{
    /**
     * Attempts for a retryable read: the first try plus two retries.
     */
    private const READ_ATTEMPTS = 3;

    public function __construct(
        private readonly Config $config,
        private readonly EsbConnectionResolver $connectionResolver,
        private readonly TokenManager $tokenManager,
        private readonly ErrorClassifier $errorClassifier,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     */
    public function get(EsbFamily $family, string $path, array $query = [], string $operation = ''): EsbEnvelope
    {
        return $this->send('GET', $family, $path, $operation, $query);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public function post(EsbFamily $family, string $path, array $body = [], string $operation = ''): EsbEnvelope
    {
        return $this->send('POST', $family, $path, $operation, body: $body);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public function put(EsbFamily $family, string $path, array $body = [], string $operation = ''): EsbEnvelope
    {
        return $this->send('PUT', $family, $path, $operation, body: $body);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public function patch(EsbFamily $family, string $path, array $body = [], string $operation = ''): EsbEnvelope
    {
        return $this->send('PATCH', $family, $path, $operation, body: $body);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public function delete(EsbFamily $family, string $path, array $body = [], string $operation = ''): EsbEnvelope
    {
        return $this->send('DELETE', $family, $path, $operation, body: $body);
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $body
     *
     * @throws EsbException
     */
    public function send(
        string $method,
        EsbFamily $family,
        string $path,
        string $operation = '',
        array $query = [],
        array $body = [],
    ): EsbEnvelope {
        $method = strtoupper($method);

        $context = new EsbContext(
            correlationId: (string) Str::uuid(),
            operation: $operation !== '' ? $operation : strtolower($method).' '.$path,
            family: $family,
            method: $method,
            path: $path,
        );

        $startedAt = now();
        $startedAtMicro = microtime(true);

        try {
            [$envelope, $attempts, $httpStatus] = $this->attempt($method, $family, $path, $query, $body, $context);
        } catch (EsbException $exception) {
            $this->record(
                $exception->context->withTiming($this->elapsedMs($startedAtMicro)),
                $startedAt,
                outcome: $exception->kind(),
                esbCode: $exception->esbCode,
                safeErrorMessage: $exception->safeMessage(),
            );

            Log::warning('ESB call failed', $exception->toLogContext());

            throw $exception;
        }

        $this->record(
            $context->withTiming($this->elapsedMs($startedAtMicro), $httpStatus)->withRetryCount($attempts - 1),
            $startedAt,
            outcome: IntegrationLog::OUTCOME_SUCCESS,
            esbCode: $envelope->code,
        );

        return $envelope;
    }

    /**
     * Execute the request, applying the retry policy.
     *
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $body
     * @return array{EsbEnvelope, int, int|null}
     *
     * @throws EsbException
     */
    private function attempt(
        string $method,
        EsbFamily $family,
        string $path,
        array $query,
        array $body,
        EsbContext $context,
    ): array {
        $isRead = $method === 'GET';

        // Writes get exactly one attempt. No idempotency key is documented for
        // any write operation, so a repeated write can create a duplicate ERP
        // document, and a timed-out write is in an unknown state
        // (docs/05-api-integration.md).
        $maxAttempts = $isRead ? self::READ_ATTEMPTS : 1;

        $attempt = 0;
        $reauthenticated = false;
        $lastException = null;

        while ($attempt < $maxAttempts) {
            $attempt++;

            try {
                $response = $this->dispatch($method, $family, $path, $query, $body);
            } catch (Throwable $transportFailure) {
                $lastException = $this->normalizeTransportFailure(
                    $transportFailure,
                    $context->withRetryCount($attempt - 1),
                );

                if ($attempt < $maxAttempts) {
                    $this->backoff($attempt);

                    continue;
                }

                throw $lastException;
            }

            $attemptContext = $context->withRetryCount($attempt - 1)->withTiming(0, $response->status());

            $envelope = $this->parse($response, $attemptContext);

            if ($envelope->isSuccessful()) {
                return [$envelope, $attempt, $response->status()];
            }

            $exception = $this->errorClassifier->classify($envelope, $attemptContext);

            // A rejected token may simply mean another process logged in with
            // the same credentials and invalidated our session (ADR-009).
            // Discard it and try once more with a freshly obtained token.
            if ($exception->shouldInvalidateToken() && ! $reauthenticated) {
                $this->tokenManager->forget();
                $reauthenticated = true;
                $maxAttempts = max($maxAttempts, $attempt + 1);

                continue;
            }

            if ($exception->isRetryable() && $isRead && $attempt < $maxAttempts) {
                $this->backoff($attempt);

                continue;
            }

            throw $exception;
        }

        throw $lastException ?? new EsbUpstreamException(
            context: $context,
            upstreamMessage: 'The ESB request produced no response.',
        );
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $body
     */
    private function dispatch(string $method, EsbFamily $family, string $path, array $query, array $body): Response
    {
        $url = $this->connectionResolver->resolve($family).'/'.ltrim($path, '/');

        $request = Http::withToken($this->tokenManager->token())
            ->acceptJson()
            ->connectTimeout((int) $this->config->get('esb.timeouts.connect', 5))
            ->timeout((int) $this->config->get('esb.timeouts.request', 30));

        // No correlation header is sent. The vendor documents none, and sending
        // an undocumented header would be guesswork (docs/05-api-integration.md
        // marks this TBD).

        if ($query !== []) {
            $request = $request->withQueryParameters($query);
        }

        return $body === []
            ? $request->send($method, $url)
            : $request->send($method, $url, ['json' => $body]);
    }

    private function parse(Response $response, EsbContext $context): EsbEnvelope
    {
        $decoded = $response->json();

        $envelope = is_array($decoded) ? EsbEnvelope::tryFromArray($decoded) : null;

        if ($envelope !== null) {
            return $envelope;
        }

        // ESB responses are untrusted input. A body we cannot parse is never
        // guessed at (docs/10-security.md).
        if ($response->serverError()) {
            throw new EsbUpstreamException(
                context: $context,
                upstreamMessage: sprintf('ESB returned HTTP %d with an unreadable body.', $response->status()),
            );
        }

        throw new EsbMalformedResponseException(
            context: $context,
            upstreamMessage: 'ESB response was not a recognisable envelope.',
        );
    }

    private function normalizeTransportFailure(Throwable $failure, EsbContext $context): EsbException
    {
        if ($failure instanceof EsbException) {
            return $failure;
        }

        if ($failure instanceof ConnectionException) {
            // Laravel raises ConnectionException for both a refused connection
            // and an exceeded timeout; the message is the only signal.
            $isTimeout = Str::contains(strtolower($failure->getMessage()), ['timed out', 'timeout']);

            return $isTimeout
                ? new EsbTimeoutException(context: $context, upstreamMessage: $failure->getMessage(), previous: $failure)
                : new EsbConnectionException(context: $context, upstreamMessage: $failure->getMessage(), previous: $failure);
        }

        return new EsbUpstreamException(
            context: $context,
            upstreamMessage: $failure->getMessage(),
            previous: $failure,
        );
    }

    private function backoff(int $attempt): void
    {
        $baseDelayMs = (int) $this->config->get('esb.retry.base_delay_ms', 200);

        if ($baseDelayMs <= 0) {
            return;
        }

        usleep($baseDelayMs * (2 ** ($attempt - 1)) * 1000);
    }

    private function elapsedMs(float $startedAtMicro): int
    {
        return (int) round((microtime(true) - $startedAtMicro) * 1000);
    }

    /**
     * Record the call.
     *
     * Metadata only -- no headers, no payloads, no token. The Authorization
     * header and the login and refresh response bodies both carry secrets
     * (docs/05-api-integration.md).
     */
    private function record(
        EsbContext $context,
        \DateTimeInterface $startedAt,
        string $outcome,
        ?string $esbCode = null,
        ?string $safeErrorMessage = null,
    ): void {
        try {
            IntegrationLog::query()->create([
                'correlation_id' => $context->correlationId,
                'operation' => $context->operation,
                'family' => $context->family !== null ? $context->family->value : 'unknown',
                'environment' => (string) $this->config->get('esb.environment'),
                'method' => $context->method ?? 'UNKNOWN',
                'path' => $context->path ?? '',
                'outcome' => $outcome,
                'esb_code' => $esbCode,
                'safe_error_message' => $safeErrorMessage,
                'upstream_status' => $context->httpStatus,
                'duration_ms' => $context->durationMs,
                'retry_count' => $context->retryCount,
                'user_id' => Auth::id(),
                'started_at' => $startedAt,
            ]);
        } catch (Throwable $exception) {
            // Losing an integration log entry must not take down the business
            // operation that produced it.
            Log::error('Failed to write integration log', [
                ...$context->toLogContext(),
                'reason' => $exception->getMessage(),
            ]);
        }
    }
}
