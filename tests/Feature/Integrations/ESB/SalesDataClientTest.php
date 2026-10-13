<?php

namespace Tests\Feature\Integrations\ESB;

use App\Integrations\Contracts\SalesDataSink;
use App\Integrations\ESB\Clients\SalesDataClient;
use App\Integrations\ESB\Exceptions\EsbTimeoutException;
use App\Integrations\ESB\Exceptions\EsbValidationException;
use App\Models\IntegrationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\SalesDataV2;
use Tests\TestCase;

/**
 * POST {{fnb_backend}}/extv1/push/sales-data -- Push Sales Data V2, [ESB-011].
 */
class SalesDataClientTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://stg7.esb.co.id/api-fnb-backend-int/web/extv1/push/sales-data';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config()->set('esb.environment', 'staging_int');
        config()->set('esb.auth.mechanism', 'static_token');
        config()->set('esb.auth.static_token', 'a-static-api-key');
        config()->set('esb.retry.base_delay_ms', 0);
    }

    #[Test]
    public function the_contract_is_bound_to_the_esb_adapter(): void
    {
        $sink = app(SalesDataSink::class);

        $this->assertInstanceOf(SalesDataClient::class, $sink);
        $this->assertSame('esb', $sink->provider());
    }

    #[Test]
    public function it_posts_the_body_unchanged_to_the_fnb_backend_family(): void
    {
        Http::fake([self::URL => Http::response(SalesDataV2::successResponse())]);

        $receipt = app(SalesDataSink::class)->pushSalesData(SalesDataV2::payload());

        $this->assertSame('00', $receipt->status);
        $this->assertSame('2024-10-01 16:42:46', $receipt->syncDate);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === self::URL
                && $request->hasHeader('Authorization', 'Bearer a-static-api-key')
                // Byte for byte: the empty strings stay empty strings.
                && $request->data() === SalesDataV2::payload();
        });

        $this->assertDatabaseHas('integration_logs', [
            'operation' => 'sales-data.push',
            'family' => 'fnb_backend',
            'outcome' => IntegrationLog::OUTCOME_SUCCESS,
        ]);
    }

    #[Test]
    public function a_refusal_carries_the_messages_the_esb_listed(): void
    {
        Http::fake([self::URL => Http::response(SalesDataV2::notFoundResponse(), 400)]);

        try {
            app(SalesDataSink::class)->pushSalesData(SalesDataV2::payload());
            $this->fail('Expected a validation failure.');
        } catch (EsbValidationException $exception) {
            $this->assertFalse($exception->mayHaveBeenApplied());
            $this->assertSame(
                ['salesHead.memberCode', 'salesHead.visitPurposeName'],
                array_map(fn ($error) => $error->field, $exception->fieldErrors),
            );
        }
    }

    #[Test]
    public function a_timeout_is_one_attempt_whose_outcome_is_unknown(): void
    {
        $calls = 0;

        Http::fake([self::URL => function () use (&$calls) {
            $calls++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        }]);

        try {
            app(SalesDataSink::class)->pushSalesData(SalesDataV2::payload());
            $this->fail('Expected a timeout.');
        } catch (EsbTimeoutException $exception) {
            $this->assertTrue($exception->mayHaveBeenApplied());
        }

        // Retrying is the job's decision, not the transport's.
        $this->assertSame(1, $calls);
    }

    #[Test]
    public function a_success_without_a_result_still_counts_as_accepted(): void
    {
        // The envelope code is the ESB's answer. What result.status other than
        // "00" means is undocumented, so the receipt reports it rather than the
        // adapter guessing (TBD in docs/05-api-integration.md).
        Http::fake([self::URL => Http::response([...SalesDataV2::successResponse(), 'result' => null])]);

        $receipt = app(SalesDataSink::class)->pushSalesData(SalesDataV2::payload());

        $this->assertNull($receipt->status);
        $this->assertNull($receipt->syncDate);
    }
}
