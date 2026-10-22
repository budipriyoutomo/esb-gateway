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

            // FnB backend (Push Sales Data V2) uses HTTP-style numbers.
            'fnb backend bad request' => ['400', EsbValidationException::class],
            'fnb backend invalid credentials' => ['401', EsbAuthenticationException::class],
        ];
    }

    #[Test]
    public function messages_in_data_become_field_errors_when_errors_is_absent(): void
    {
        // The FnB backend lists its refusals as plain strings in `data`, not
        // as objects in `errors`. The field is the dotted path the message
        // names; a message that names none keeps a null field.
        $exception = app(ErrorClassifier::class)->classify(
            EsbEnvelope::fromArray([
                'code' => 400,
                'message' => 'Member not found: salesHead.memberCode',
                'data' => [
                    'Member not found: salesHead.memberCode',
                    'salesHead.salesNum cannot be blank',
                    'Missing attribute: salesHead.menu[0].extras.[0].qty',
                    'Something went wrong',
                    ['not' => 'a string'],
                    '   ',
                ],
            ]),
            new EsbContext(correlationId: 'corr-1', operation: 'sales-data.push'),
        );

        $this->assertInstanceOf(EsbValidationException::class, $exception);
        $this->assertSame(
            [
                ['row' => null, 'field' => 'salesHead.memberCode', 'message' => 'Member not found: salesHead.memberCode'],
                ['row' => null, 'field' => 'salesHead.salesNum', 'message' => 'salesHead.salesNum cannot be blank'],
                ['row' => null, 'field' => 'salesHead.menu[0].extras.[0].qty', 'message' => 'Missing attribute: salesHead.menu[0].extras.[0].qty'],
                ['row' => null, 'field' => null, 'message' => 'Something went wrong'],
            ],
            array_map(fn ($error) => $error->toArray(), $exception->fieldErrors),
        );
    }

    #[Test]
    public function an_oms_master_refusal_carries_its_field_errors_as_json_in_the_message(): void
    {
        // ESB OMS master operations (EC0110, [ESB-048]): the per-field
        // errors are a JSON object inside `message`.
        $exception = app(ErrorClassifier::class)->classify(
            EsbEnvelope::fromArray([
                'code' => 'EC0110',
                'status' => 'failed',
                'message' => '{"menuCategoryName":["Menu Category Name cannot be blank."],"salesAccount":["Sales    Test not found"," "],"bad key!":["dropped field"]}',
                'result' => [],
            ]),
            new EsbContext(correlationId: 'corr-1', operation: 'menu-categories.create'),
        );

        $this->assertInstanceOf(EsbValidationException::class, $exception);
        $this->assertSame(
            [
                ['row' => null, 'field' => 'menuCategoryName', 'message' => 'Menu Category Name cannot be blank.'],
                ['row' => null, 'field' => 'salesAccount', 'message' => 'Sales    Test not found'],
                ['row' => null, 'field' => null, 'message' => 'dropped field'],
            ],
            array_map(fn ($error) => $error->toArray(), $exception->fieldErrors),
        );
    }

    #[Test]
    public function an_oms_master_refusal_with_a_plain_message_keeps_it_for_the_form(): void
    {
        $exception = app(ErrorClassifier::class)->classify(
            EsbEnvelope::fromArray([
                'code' => 'EC0110',
                'message' => 'Menu Category not found. Invalid menuCategoryID',
                'result' => [],
            ]),
            new EsbContext(correlationId: 'corr-1', operation: 'menu-categories.update'),
        );

        $this->assertSame(
            [['row' => null, 'field' => null, 'message' => 'Menu Category not found. Invalid menuCategoryID']],
            array_map(fn ($error) => $error->toArray(), $exception->fieldErrors),
        );
    }

    #[Test]
    public function only_an_oms_master_refusal_has_its_message_read_as_errors(): void
    {
        $exception = app(ErrorClassifier::class)->classify(
            EsbEnvelope::fromArray(['code' => 'EC03100003', 'message' => '{"coaNo":["required"]}']),
            new EsbContext(correlationId: 'corr-1', operation: 'x'),
        );

        $this->assertSame([], $exception->fieldErrors);
    }

    #[Test]
    public function field_errors_keyed_by_field_in_data_are_read(): void
    {
        // The OMS promotion create ([ESB-050]): `data` maps each field to
        // its messages.
        $exception = app(ErrorClassifier::class)->classify(
            EsbEnvelope::fromArray([
                'code' => 400,
                'message' => '{Attribute} cannot be blank.',
                'data' => [
                    'branchCode' => ['Branch Code cannot be blank.'],
                    'endDate' => 'End date must be greater then start date 2023-02-23 12:20:00',
                    'notes' => [['not' => 'a string']],
                ],
            ]),
            new EsbContext(correlationId: 'corr-1', operation: 'promotions.create'),
        );

        $this->assertInstanceOf(EsbValidationException::class, $exception);
        $this->assertSame(
            [
                ['row' => null, 'field' => 'branchCode', 'message' => 'Branch Code cannot be blank.'],
                ['row' => null, 'field' => 'endDate', 'message' => 'End date must be greater then start date 2023-02-23 12:20:00'],
            ],
            array_map(fn ($error) => $error->toArray(), $exception->fieldErrors),
        );
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

    // ----------------------------------------------------------- errors[]

    private function classifyWithErrors(mixed $errors, string $code = 'EC03100003'): object
    {
        return app(ErrorClassifier::class)->classify(
            EsbEnvelope::fromArray([
                'code' => $code,
                'message' => 'Validation Error',
                'status' => 'fail',
                'errors' => $errors,
            ]),
            new EsbContext(correlationId: 'corr-1', operation: 'purchase-request.import'),
        );
    }

    #[Test]
    public function it_carries_the_per_field_detail_of_errors(): void
    {
        $exception = $this->classifyWithErrors([
            ['attribute' => 'branchID', 'field' => 'branchID', 'code' => 'EC03100003', 'message' => 'branchID is required'],
            ['attribute' => 'qty', 'field' => 'qty', 'code' => 'EC03100003', 'message' => 'qty must be greater than 0', 'row' => 4],
        ]);

        $this->assertCount(2, $exception->fieldErrors);
        $this->assertSame('branchID', $exception->fieldErrors[0]->field);
        $this->assertNull($exception->fieldErrors[0]->row);
        $this->assertSame('branchID is required', $exception->fieldErrors[0]->message);
        $this->assertSame('qty', $exception->fieldErrors[1]->field);
        $this->assertSame(4, $exception->fieldErrors[1]->row);
    }

    #[Test]
    public function field_detail_falls_back_to_attribute_when_field_is_absent(): void
    {
        $exception = $this->classifyWithErrors([
            ['attribute' => 'requiredDate', 'message' => 'requiredDate is invalid'],
        ]);

        $this->assertSame('requiredDate', $exception->fieldErrors[0]->field);
    }

    #[Test]
    public function errors_that_are_not_a_list_of_objects_are_ignored(): void
    {
        $this->assertSame([], $this->classifyWithErrors(null)->fieldErrors);
        $this->assertSame([], $this->classifyWithErrors('boom')->fieldErrors);

        // Entries without a usable message say nothing; drop them rather
        // than show an empty bullet.
        $exception = $this->classifyWithErrors([
            'not-an-object',
            ['field' => 'branchID'],
            ['field' => 'branchID', 'message' => '   '],
            ['field' => 'branchID', 'message' => 'branchID is required'],
        ]);

        $this->assertCount(1, $exception->fieldErrors);
    }

    #[Test]
    public function field_detail_is_bounded(): void
    {
        // Untrusted input shown to a user: a runaway message or list must
        // not become a runaway page.
        $exception = $this->classifyWithErrors([
            ...array_fill(0, 500, ['field' => 'qty', 'message' => 'qty is invalid', 'row' => 2]),
        ]);
        $this->assertCount(100, $exception->fieldErrors);

        $long = $this->classifyWithErrors([['field' => 'qty', 'message' => str_repeat('x', 5000)]]);
        $this->assertLessThanOrEqual(300, mb_strlen($long->fieldErrors[0]->message));
    }

    #[Test]
    public function a_field_name_that_is_not_an_identifier_is_dropped(): void
    {
        $exception = $this->classifyWithErrors([
            ['field' => '<script>alert(1)</script>', 'message' => 'bad', 'row' => -3],
        ]);

        $this->assertNull($exception->fieldErrors[0]->field);
        $this->assertNull($exception->fieldErrors[0]->row);
        $this->assertSame('bad', $exception->fieldErrors[0]->message);
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
