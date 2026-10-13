<?php

namespace App\Integrations\ESB\Exceptions;

use App\Integrations\ESB\EsbContext;
use App\Integrations\ESB\Responses\EsbFieldError;
use RuntimeException;
use Throwable;

/**
 * Base class for every normalized ESB failure.
 *
 * Carries the diagnostic context needed to trace a failure, while keeping the
 * upstream message away from end users: it can contain internal document
 * numbers and, in at least one documented case, an upstream PHP notice with a
 * file path (docs/05-api-integration.md).
 */
abstract class EsbException extends RuntimeException
{
    /**
     * @param  list<EsbFieldError>  $fieldErrors  The envelope's `errors[]`, filtered.
     *                                            Unlike upstreamMessage, these are
     *                                            meant for the person who filled the
     *                                            form or sheet.
     */
    public function __construct(
        public readonly EsbContext $context,
        public readonly ?string $esbCode = null,
        public readonly ?string $upstreamMessage = null,
        ?Throwable $previous = null,
        public readonly array $fieldErrors = [],
    ) {
        parent::__construct($this->buildInternalMessage(), 0, $previous);
    }

    /**
     * A short, stable label for this failure class, used in logs and metrics.
     */
    abstract public function kind(): string;

    /**
     * Whether a cached ESB token should be discarded because of this failure.
     */
    public function shouldInvalidateToken(): bool
    {
        return false;
    }

    /**
     * Whether the operation may be retried without further thought.
     *
     * Note that safety also depends on the HTTP method: writes are never
     * retried automatically, because no idempotency key is documented and a
     * timed-out write is in an unknown state.
     */
    public function isRetryable(): bool
    {
        return false;
    }

    /**
     * Whether a write that failed this way may nonetheless have been applied.
     *
     * False means the ESB answered with a definite refusal, so the caller can
     * report that nothing happened. True means there was no usable answer, or
     * one that does not say what happened: the ERP document is in an unknown
     * state, and a human must check it (a GET by document number) before
     * trying again. Meaningless for reads.
     */
    public function mayHaveBeenApplied(): bool
    {
        return false;
    }

    /**
     * A message safe to show an end user.
     *
     * Includes the correlation ID, which is what turns "it broke" into an
     * actionable support request.
     */
    public function safeMessage(): string
    {
        return sprintf(
            '%s (reference: %s)',
            $this->userFacingSummary(),
            $this->context->correlationId,
        );
    }

    protected function userFacingSummary(): string
    {
        return 'The ERP service could not complete this request.';
    }

    /**
     * Diagnostic detail for logs. Contains no credentials.
     *
     * @return array<string, mixed>
     */
    public function toLogContext(): array
    {
        return [
            ...$this->context->toLogContext(),
            'kind' => $this->kind(),
            'esb_code' => $this->esbCode,
            'esb_message' => $this->upstreamMessage,
        ];
    }

    private function buildInternalMessage(): string
    {
        return sprintf(
            '[%s] %s failed (code: %s): %s',
            $this->context->correlationId,
            $this->context->operation,
            $this->esbCode ?? 'none',
            $this->upstreamMessage ?? 'no upstream message',
        );
    }
}
