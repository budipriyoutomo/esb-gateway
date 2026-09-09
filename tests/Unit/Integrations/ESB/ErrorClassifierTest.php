<?php

namespace Tests\Unit\Integrations\ESB;

use App\Integrations\ESB\ErrorClassifier;
use App\Integrations\ESB\EsbContext;
use App\Integrations\ESB\Exceptions\EsbAuthenticationException;
use App\Integrations\ESB\Exceptions\EsbAuthorizationException;
use App\Integrations\ESB\Exceptions\EsbBusinessRejectionException;
use App\Integrations\ESB\Exceptions\EsbNotFoundException;
use App\Integrations\ESB\Exceptions\EsbUnknownException;
use App\Integrations\ESB\Exceptions\EsbValidationException;
use App\Integrations\ESB\Responses\EsbEnvelope;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Maps ESB status codes onto application error classes.
 *
 * The mapping is transcribed from docs/05-api-integration.md, which was itself
 * transcribed from the vendor documentation.
 */
class ErrorClassifierTest extends TestCase
{
    private function classify(string $code, string $message = 'irrelevant'): object
    {
        return app(ErrorClassifier::class)->classify(
            EsbEnvelope::fromArray([
                'code' => $code,
                'message' => $message,
                'status' => 'fail',
            ]),
            new EsbContext(correlationId: 'corr-1', operation: 'product.list'),
        );
    }

    /**
     * @return array<string, array{string, class-string}>
     */
    public static function documentedCodes(): array
    {
        return [
            'unauthorized' => ['EC03100001', EsbAuthenticationException::class],
            'unauthorized alt' => ['EC011401', EsbAuthenticationException::class],

            'no access to resource' => ['EC03100002', EsbAuthorizationException::class],
            'access denied' => ['EC03100005', EsbAuthorizationException::class],
            'no access to transaction' => ['EC03100009', EsbAuthorizationException::class],

            'validation error' => ['EC03100003', EsbValidationException::class],
            'required field' => ['EC03100023', EsbValidationException::class],
            'min items' => ['EC03100024', EsbValidationException::class],
            'max length' => ['EC03100025', EsbValidationException::class],
            'sort charset' => ['EC03100026', EsbValidationException::class],
            'qty range' => ['EC03100027', EsbValidationException::class],
            'date format' => ['EC03100036', EsbValidationException::class],
            'page minimum' => ['EC03100039', EsbValidationException::class],
            'limit cap' => ['EC03100040', EsbValidationException::class],
            'alnum charset' => ['EC03100042', EsbValidationException::class],
            'alnum space charset' => ['EC03100043', EsbValidationException::class],
            'conditional required' => ['EC03100045', EsbValidationException::class],
            'no emojis' => ['EC03100046', EsbValidationException::class],
            'voucher minimum' => ['EC03110000', EsbValidationException::class],
            'validation error alt' => ['EC04100001', EsbValidationException::class],
            'oms validation' => ['EC0110', EsbValidationException::class],

            'not found' => ['EC03100004', EsbNotFoundException::class],
            'referenced entity not found' => ['EC03100038', EsbNotFoundException::class],

            'already taken' => ['EC03100029', EsbBusinessRejectionException::class],
            'custom business rule' => ['EC031Custom', EsbBusinessRejectionException::class],
            'budget domain' => ['EC04100022', EsbBusinessRejectionException::class],

            'unknown error' => ['EC03100032', EsbUnknownException::class],
            'upstream defect' => ['EC0118', EsbUnknownException::class],
        ];
    }

    /**
     * @param  class-string  $expected
     */
    #[Test]
    #[DataProvider('documentedCodes')]
    public function it_maps_documented_codes_to_error_classes(string $code, string $expected): void
    {
        $this->assertInstanceOf($expected, $this->classify($code));
    }

    #[Test]
    public function an_unrecognised_code_becomes_unknown_and_is_logged(): void
    {
        // New vendor codes must surface rather than being silently swallowed.
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => str_contains($message, 'Unrecognised')
                && $context['esb_code'] === 'EC99999999');

        $this->assertInstanceOf(EsbUnknownException::class, $this->classify('EC99999999'));
    }

    #[Test]
    public function a_documented_code_is_not_logged_as_unrecognised(): void
    {
        Log::shouldReceive('warning')->never();

        $this->classify('EC03100003');
    }

    #[Test]
    public function classification_ignores_the_message_entirely(): void
    {
        // EC03100001 is documented as both "Unauthorized" and "Invalid Token",
        // and in at least one example carries a business rejection message.
        // Branching on the message would misclassify all three.
        $this->assertInstanceOf(
            EsbAuthenticationException::class,
            $this->classify('EC03100001', 'This transaction is linked with Product Sales #SL202511210004'),
        );

        $this->assertInstanceOf(
            EsbValidationException::class,
            $this->classify('EC03100003', 'Unauthorized'),
        );
    }

    #[Test]
    public function an_authentication_failure_asks_for_the_token_to_be_discarded(): void
    {
        $exception = $this->classify('EC03100001');

        $this->assertInstanceOf(EsbAuthenticationException::class, $exception);
        $this->assertTrue($exception->shouldInvalidateToken());
    }

    #[Test]
    public function it_carries_diagnostic_context(): void
    {
        $exception = $this->classify('EC03100004', 'purchase order not found');

        $this->assertSame('EC03100004', $exception->esbCode);
        $this->assertSame('purchase order not found', $exception->upstreamMessage);
        $this->assertSame('corr-1', $exception->context->correlationId);
        $this->assertSame('product.list', $exception->context->operation);
    }

    #[Test]
    public function the_user_facing_message_does_not_leak_upstream_detail(): void
    {
        $exception = $this->classify(
            'EC03100032',
            'Undefined index: menuTemplateID in /var/www/esb/app/Controller.php line 220',
        );

        $safe = $exception->safeMessage();

        $this->assertStringNotContainsString('/var/www', $safe);
        $this->assertStringNotContainsString('menuTemplateID', $safe);
        $this->assertStringContainsString('corr-1', $safe, 'The correlation ID is what makes a support request actionable.');
    }
}
