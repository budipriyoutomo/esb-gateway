<?php

namespace App\Integrations\ESB\Responses;

use App\Integrations\ESB\ErrorCode;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * The response envelope every documented ESB operation returns.
 *
 * Classification is on `code` alone. The vendor documentation carries 942
 * distinct code/message pairs for only 29 distinct codes -- messages are
 * free-form, interpolate document numbers, and vary in capitalisation. The
 * `status` field is no better: both "fail" and "failed" appear, and some
 * examples put unrelated domain values there. See docs/05-api-integration.md.
 */
readonly class EsbEnvelope
{
    /**
     * The ESB reports Asia/Jakarta wall-clock time with no offset.
     */
    private const UPSTREAM_TIMEZONE = 'Asia/Jakarta';

    /**
     * @param  array<string, mixed>  $body
     */
    public function __construct(
        public string $code,
        public ?string $message = null,
        public ?string $path = null,
        public ?string $timestamp = null,
        public ?string $status = null,
        public mixed $result = null,
        public mixed $errors = null,
        public array $body = [],
    ) {}

    /**
     * @param  array<string, mixed>  $body
     *
     * @throws \InvalidArgumentException when the body carries no code
     */
    public static function fromArray(array $body): self
    {
        $envelope = self::tryFromArray($body);

        if ($envelope === null) {
            throw new \InvalidArgumentException('ESB response body has no "code" field.');
        }

        return $envelope;
    }

    /**
     * Parse a body, or return null when it is not a recognisable envelope.
     *
     * @param  array<string, mixed>  $body
     */
    public static function tryFromArray(array $body): ?self
    {
        $code = $body['code'] ?? null;

        if (! is_string($code) && ! is_int($code)) {
            return null;
        }

        return new self(
            code: (string) $code,
            message: is_string($body['message'] ?? null) ? $body['message'] : null,
            path: is_string($body['path'] ?? null) ? $body['path'] : null,
            timestamp: is_string($body['timestamp'] ?? null) ? $body['timestamp'] : null,
            status: is_string($body['status'] ?? null) ? $body['status'] : null,
            result: $body['result'] ?? null,
            errors: $body['errors'] ?? null,
            body: $body,
        );
    }

    public function isSuccessful(): bool
    {
        return $this->code === ErrorCode::SUCCESS;
    }

    /**
     * The result as an array, or an empty array when it is absent or scalar.
     *
     * @return array<mixed>
     */
    public function resultArray(): array
    {
        return is_array($this->result) ? $this->result : [];
    }

    /**
     * Rows from a paginated list result.
     *
     * @return list<array<string, mixed>>
     */
    public function rows(): array
    {
        $data = $this->resultArray()['data'] ?? null;

        if (! is_array($data)) {
            return [];
        }

        return array_values(array_filter($data, 'is_array'));
    }

    /**
     * Pagination state, or null when the result is not a paginated list.
     */
    public function pagination(): ?EsbPagination
    {
        $result = $this->resultArray();

        if (! isset($result['page'], $result['limit'], $result['count'])) {
            return null;
        }

        if (! is_numeric($result['page']) || ! is_numeric($result['limit']) || ! is_numeric($result['count'])) {
            return null;
        }

        return new EsbPagination(
            page: (int) $result['page'],
            limit: (int) $result['limit'],
            count: (int) $result['count'],
        );
    }

    /**
     * The envelope timestamp converted to UTC.
     *
     * Returns null when the value is absent or unparseable -- a malformed
     * timestamp is a diagnostic annoyance, not a reason to discard an otherwise
     * valid business response.
     */
    public function timestampAsUtc(): ?CarbonImmutable
    {
        if ($this->timestamp === null || trim($this->timestamp) === '') {
            return null;
        }

        try {
            $parsed = CarbonImmutable::createFromFormat(
                'Y-m-d H:i:s',
                $this->timestamp,
                self::UPSTREAM_TIMEZONE,
            );
        } catch (Throwable) {
            return null;
        }

        if (! $parsed instanceof CarbonImmutable) {
            return null;
        }

        return $parsed->setTimezone('UTC');
    }
}
