<?php

namespace Tests\Unit\Integrations\ESB;

use App\Integrations\ESB\EsbContext;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbAuthenticationException;
use App\Integrations\ESB\Exceptions\EsbAuthorizationException;
use App\Integrations\ESB\Exceptions\EsbBusinessRejectionException;
use App\Integrations\ESB\Exceptions\EsbConnectionException;
use App\Integrations\ESB\Exceptions\EsbException;
use App\Integrations\ESB\Exceptions\EsbMalformedResponseException;
use App\Integrations\ESB\Exceptions\EsbNotFoundException;
use App\Integrations\ESB\Exceptions\EsbTimeoutException;
use App\Integrations\ESB\Exceptions\EsbUnknownException;
use App\Integrations\ESB\Exceptions\EsbUpstreamException;
use App\Integrations\ESB\Exceptions\EsbValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Whether a failed write may nonetheless have been applied by the ESB.
 *
 * No idempotency key is documented, so a write is never retried
 * automatically. What a caller must know instead is whether the failure is a
 * definite "no" -- safe to tell the user nothing happened -- or leaves the ERP
 * document in an unknown state that a human must check before trying again.
 */
class WriteOutcomeTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string<EsbException>, bool}>
     */
    public static function failures(): iterable
    {
        // The ESB answered and said no: the write was refused before it did
        // anything.
        yield 'authentication' => [EsbAuthenticationException::class, false];
        yield 'authorization' => [EsbAuthorizationException::class, false];
        yield 'business rejection' => [EsbBusinessRejectionException::class, false];
        yield 'not found' => [EsbNotFoundException::class, false];
        yield 'validation' => [EsbValidationException::class, false];

        // No usable answer, or an answer that does not say what happened.
        yield 'timeout' => [EsbTimeoutException::class, true];
        // A connection failure can happen after the request was sent, and
        // the transport does not reliably say which.
        yield 'connection' => [EsbConnectionException::class, true];
        yield 'upstream' => [EsbUpstreamException::class, true];
        yield 'malformed response' => [EsbMalformedResponseException::class, true];
        // EC03100032 "Unknown Error" covers both generic failures and some
        // business rejections, so it cannot be read as a definite "no".
        yield 'unknown' => [EsbUnknownException::class, true];
    }

    /**
     * @param  class-string<EsbException>  $class
     */
    #[Test]
    #[DataProvider('failures')]
    public function it_says_whether_a_write_may_have_been_applied(string $class, bool $expected): void
    {
        $exception = new $class(new EsbContext(
            correlationId: 'test',
            operation: 'purchase-request.authorize',
            family: EsbFamily::Core,
            method: 'PATCH',
            path: '/purchase/purchase-request/PR1/authorize',
        ));

        $this->assertSame($expected, $exception->mayHaveBeenApplied());
    }
}
