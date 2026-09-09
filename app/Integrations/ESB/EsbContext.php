<?php

namespace App\Integrations\ESB;

/**
 * Diagnostic context for one ESB operation.
 *
 * Carried on every ESB exception so a failure can be traced without the caller
 * having to reconstruct where it came from. Contains no credentials.
 */
readonly class EsbContext
{
    public function __construct(
        public string $correlationId,
        public string $operation,
        public ?EsbFamily $family = null,
        public ?string $method = null,
        public ?string $path = null,
        public ?int $durationMs = null,
        public ?int $httpStatus = null,
        public int $retryCount = 0,
    ) {}

    public function withTiming(int $durationMs, ?int $httpStatus = null): self
    {
        return new self(
            correlationId: $this->correlationId,
            operation: $this->operation,
            family: $this->family,
            method: $this->method,
            path: $this->path,
            durationMs: $durationMs,
            httpStatus: $httpStatus ?? $this->httpStatus,
            retryCount: $this->retryCount,
        );
    }

    public function withRetryCount(int $retryCount): self
    {
        return new self(
            correlationId: $this->correlationId,
            operation: $this->operation,
            family: $this->family,
            method: $this->method,
            path: $this->path,
            durationMs: $this->durationMs,
            httpStatus: $this->httpStatus,
            retryCount: $retryCount,
        );
    }

    /**
     * Safe to log: no credentials, no payloads.
     *
     * @return array<string, mixed>
     */
    public function toLogContext(): array
    {
        return array_filter([
            'correlation_id' => $this->correlationId,
            'operation' => $this->operation,
            'family' => $this->family?->value,
            'method' => $this->method,
            'path' => $this->path,
            'duration_ms' => $this->durationMs,
            'upstream_status' => $this->httpStatus,
            'retry_count' => $this->retryCount,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
