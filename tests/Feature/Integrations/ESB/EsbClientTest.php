<?php

namespace Tests\Feature\Integrations\ESB;

use App\Integrations\ESB\Clients\EsbClient;
use App\Integrations\ESB\EsbFamily;
use App\Integrations\ESB\Exceptions\EsbAuthenticationException;
use App\Integrations\ESB\Exceptions\EsbBusinessRejectionException;
use App\Integrations\ESB\Exceptions\EsbConnectionException;
use App\Integrations\ESB\Exceptions\EsbMalformedResponseException;
use App\Integrations\ESB\Exceptions\EsbTimeoutException;
use App\Integrations\ESB\Exceptions\EsbUpstreamException;
use App\Integrations\ESB\Exceptions\EsbValidationException;
use App\Models\IntegrationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EsbClientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config()->set('esb.environment', 'staging_int');
        config()->set('esb.auth.mechanism', 'static_token');
        config()->set('esb.auth.static_token', 'a-static-api-key');
        // No real sleeping between retries in tests.
        config()->set('esb.retry.base_delay_ms', 0);
    }

    private function client(): EsbClient
    {
        return app(EsbClient::class);
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function success(array $result = []): array
    {
        return [
            'path' => 'https://stg7.esb.co.id/core-int/product/list',
            'timestamp' => '2024-05-28 18:20:51',
            'status' => 'ok',
            'code' => 'EC03100000',
            'message' => 'OK',
            'result' => $result,
            'errors' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function failure(string $code, string $message): array
    {
        return [
            'path' => 'https://stg7.esb.co.id/core-int/product/list',
            'timestamp' => '2024-05-28 18:20:51',
            'status' => 'fail',
            'code' => $code,
            'message' => $message,
            'result' => null,
            'errors' => null,
        ];
    }

    #[Test]
    public function it_sends_the_bearer_token_to_the_family_base_url(): void
    {
        Http::fake(['*' => Http::response($this->success())]);

        $this->client()->get(EsbFamily::Core, '/product/list', operation: 'product.list');

        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://stg7.esb.co.id/core-int/product/list')
            && $request->header('Authorization') === ['Bearer a-static-api-key']);
    }

    #[Test]
    public function a_different_family_goes_to_a_different_host(): void
    {
        Http::fake(['*' => Http::response($this->success())]);

        $this->client()->get(EsbFamily::FnbBackend, '/corev1/master/get-menu', operation: 'oms.menu.get');

        Http::assertSent(fn (Request $request): bool => str_starts_with(
            $request->url(),
            'https://stg7.esb.co.id/api-fnb-backend-int/web/corev1/master/get-menu',
        ));
    }

    #[Test]
    public function it_returns_the_parsed_envelope_on_success(): void
    {
        Http::fake(['*' => Http::response($this->success(['page' => 1, 'limit' => 20, 'count' => 3, 'data' => []]))]);

        $envelope = $this->client()->get(EsbFamily::Core, '/product/list', operation: 'product.list');

        $this->assertTrue($envelope->isSuccessful());
        $this->assertSame(3, $envelope->pagination()?->count);
    }

    #[Test]
    public function it_classifies_a_failure_envelope(): void
    {
        Http::fake(['*' => Http::response($this->failure('EC03100003', 'Validation Error'))]);

        $this->expectException(EsbValidationException::class);

        $this->client()->get(EsbFamily::Core, '/product/list', operation: 'product.list');
    }

    #[Test]
    public function a_two_hundred_response_carrying_a_failure_code_still_fails(): void
    {
        // The vendor reports errors through the envelope, so HTTP 200 is not
        // evidence of success (docs/05-api-integration.md).
        Http::fake(['*' => Http::response($this->failure('EC03100029', 'SupplierCode data already taken'), 200)]);

        $this->expectException(EsbBusinessRejectionException::class);

        $this->client()->get(EsbFamily::Core, '/supplier', operation: 'supplier.list');
    }

    #[Test]
    public function a_non_json_body_is_malformed_not_guessed_at(): void
    {
        Http::fake(['*' => Http::response('<html>502 Bad Gateway</html>', 200)]);

        $this->expectException(EsbMalformedResponseException::class);

        $this->client()->get(EsbFamily::Core, '/product/list', operation: 'product.list');
    }

    #[Test]
    public function a_read_is_retried_after_a_connection_failure(): void
    {
        Http::fake(['*' => Http::sequence()
            ->pushFailedConnection()
            ->push($this->success()),
        ]);

        $envelope = $this->client()->get(EsbFamily::Core, '/product/list', operation: 'product.list');

        $this->assertTrue($envelope->isSuccessful());
        Http::assertSentCount(2);
    }

    #[Test]
    public function a_read_gives_up_after_the_retry_budget(): void
    {
        // A fake that throws is not recorded by Http::assertSentCount, so count
        // the attempts directly.
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;

            throw new ConnectionException('Connection refused');
        });

        try {
            $this->client()->get(EsbFamily::Core, '/product/list', operation: 'product.list');
            $this->fail('Expected EsbConnectionException.');
        } catch (EsbConnectionException) {
            // expected
        }

        // One initial attempt plus two retries.
        $this->assertSame(3, $attempts);
    }

    #[Test]
    public function a_write_is_never_retried_automatically(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;

            throw new ConnectionException('Connection refused');
        });

        try {
            $this->client()->post(EsbFamily::Core, '/purchase/purchase-request', operation: 'purchase-request.create');
        } catch (EsbConnectionException) {
            // expected
        }

        // No idempotency key is documented for any write operation, so a
        // repeated write could create a duplicate ERP document.
        $this->assertSame(1, $attempts);
    }

    // ---------------------------------------------- reads sent as POST (ADR-025)

    #[Test]
    public function a_post_read_is_sent_as_a_post_with_its_body(): void
    {
        Http::fake(['*' => Http::response($this->success(['rows' => []]))]);

        $this->client()->postRead(EsbFamily::Core, '/accounting/general-ledger', ['coaNo' => '1101'], 'general-ledger.list');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && str_ends_with($request->url(), '/accounting/general-ledger')
            && $request['coaNo'] === '1101');
    }

    #[Test]
    public function a_post_read_is_retried_after_a_connection_failure(): void
    {
        Http::fake(['*' => Http::sequence()
            ->pushFailedConnection()
            ->push($this->success()),
        ]);

        $envelope = $this->client()->postRead(EsbFamily::Core, '/accounting/general-ledger', operation: 'general-ledger.list');

        $this->assertTrue($envelope->isSuccessful());
        Http::assertSentCount(2);
    }

    #[Test]
    public function a_post_read_gives_up_after_the_read_retry_budget(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        try {
            $this->client()->postRead(EsbFamily::Core, '/accounting/general-ledger', operation: 'general-ledger.list');
            $this->fail('Expected EsbTimeoutException.');
        } catch (EsbTimeoutException) {
            // expected
        }

        $this->assertSame(3, $attempts);
    }

    #[Test]
    public function a_post_read_is_not_retried_on_a_validation_failure(): void
    {
        Http::fake(['*' => Http::response($this->failure('EC03100003', 'coaNo is required'), 422)]);

        try {
            $this->client()->postRead(EsbFamily::Core, '/accounting/general-ledger', operation: 'general-ledger.list');
        } catch (EsbValidationException) {
            // expected
        }

        Http::assertSentCount(1);
    }

    #[Test]
    public function a_post_read_is_recorded_as_a_post_in_the_integration_log(): void
    {
        Http::fake(['*' => Http::response($this->success())]);

        $this->client()->postRead(EsbFamily::Core, '/accounting/general-ledger', operation: 'general-ledger.list');

        $log = IntegrationLog::query()->sole();
        $this->assertSame('POST', $log->method);
        $this->assertSame(0, $log->retry_count);
    }

    #[Test]
    public function a_file_upload_is_sent_as_multipart_with_the_bearer_token(): void
    {
        Http::fake(['*' => Http::response($this->success())]);

        $this->client()->upload(
            EsbFamily::Core,
            '/purchase/purchase-request/import',
            field: 'file',
            contents: 'binary-xlsx-bytes',
            filename: 'requests.xlsx',
            operation: 'purchase-request.import',
        );

        Http::assertSentCount(1);
        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://stg7.esb.co.id/core-int/purchase/purchase-request/import'
                && $request->isMultipart()
                && $request->hasFile('file', 'binary-xlsx-bytes', 'requests.xlsx')
                && $request->hasHeader('Authorization', 'Bearer a-static-api-key');
        });

        $this->assertSame('purchase-request.import', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function a_file_upload_is_a_write_and_is_never_retried(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        try {
            $this->client()->upload(EsbFamily::Core, '/purchase/purchase-request/import', 'file', 'bytes', 'a.xlsx');
        } catch (EsbTimeoutException) {
            // expected
        }

        $this->assertSame(1, $attempts);
    }

    #[Test]
    public function a_write_is_not_retried_on_a_server_error_either(): void
    {
        Http::fake(['*' => Http::response('upstream exploded', 500)]);

        try {
            $this->client()->post(EsbFamily::Core, '/purchase/purchase-request', operation: 'purchase-request.create');
        } catch (EsbUpstreamException) {
            // expected
        }

        Http::assertSentCount(1);
    }

    #[Test]
    public function a_validation_failure_is_not_retried(): void
    {
        Http::fake(['*' => Http::response($this->failure('EC03100003', 'Validation Error'))]);

        try {
            $this->client()->get(EsbFamily::Core, '/product/list', operation: 'product.list');
        } catch (EsbValidationException) {
            // expected
        }

        // Repeating a request the ESB already rejected as invalid just wastes
        // the ESB's time and ours.
        Http::assertSentCount(1);
    }

    #[Test]
    public function an_expired_token_is_discarded_and_the_request_retried_once(): void
    {
        config()->set('esb.auth.mechanism', 'login');
        config()->set('esb.auth.username', 'gateway-integration');
        config()->set('esb.auth.password', 'not-a-real-password');

        Http::fake([
            '*/auth/login' => Http::sequence()
                ->push($this->success(['accessToken' => 'token-1', 'refreshToken' => 'refresh-1']))
                ->push($this->success(['accessToken' => 'token-2', 'refreshToken' => 'refresh-2'])),
            '*/product/list' => Http::sequence()
                ->push($this->failure('EC03100001', 'Invalid Token'))
                ->push($this->success()),
        ]);

        $envelope = $this->client()->get(EsbFamily::Core, '/product/list', operation: 'product.list');

        $this->assertTrue($envelope->isSuccessful());

        // The second product request must carry the newly obtained token --
        // this is the path that recovers from another process invalidating our
        // session (ADR-009).
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/product/list')
            && $request->header('Authorization') === ['Bearer token-2']);
    }

    #[Test]
    public function a_persistent_authentication_failure_gives_up(): void
    {
        Http::fake(['*' => Http::response($this->failure('EC03100001', 'Unauthorized'))]);

        $this->expectException(EsbAuthenticationException::class);

        try {
            $this->client()->get(EsbFamily::Core, '/product/list', operation: 'product.list');
        } finally {
            // One initial attempt plus a single re-authenticated retry.
            Http::assertSentCount(2);
        }
    }

    #[Test]
    public function it_records_a_successful_call_in_the_integration_log(): void
    {
        Http::fake(['*' => Http::response($this->success())]);

        $this->client()->get(EsbFamily::Core, '/product/list', operation: 'product.list');

        $log = IntegrationLog::query()->sole();

        $this->assertSame('product.list', $log->operation);
        $this->assertSame('core', $log->family);
        $this->assertSame('staging_int', $log->environment);
        $this->assertSame('success', $log->outcome);
        $this->assertSame('EC03100000', $log->esb_code);
        $this->assertSame(200, $log->upstream_status);
        $this->assertNotNull($log->correlation_id);
        $this->assertNotNull($log->duration_ms);
    }

    #[Test]
    public function it_records_a_failed_call_with_a_safe_message(): void
    {
        Http::fake(['*' => Http::response($this->failure('EC0118', 'Undefined index: menuTemplateID in /var/www/esb/Controller.php'))]);

        try {
            $this->client()->get(EsbFamily::Core, '/product/list', operation: 'product.list');
        } catch (\Throwable) {
            // expected
        }

        $log = IntegrationLog::query()->sole();

        $this->assertSame('unknown', $log->outcome);
        $this->assertSame('EC0118', $log->esb_code);

        // The upstream message can carry internal paths and document numbers,
        // so what is stored for display is the safe summary.
        $this->assertStringNotContainsString('/var/www', (string) $log->safe_error_message);
    }

    #[Test]
    public function the_integration_log_never_stores_the_token_or_credentials(): void
    {
        config()->set('esb.auth.static_token', 'super-secret-api-key');
        Http::fake(['*' => Http::response($this->success())]);

        $this->client()->get(EsbFamily::Core, '/product/list', operation: 'product.list');

        $raw = json_encode(IntegrationLog::query()->sole()->toArray());

        $this->assertIsString($raw);
        $this->assertStringNotContainsString('super-secret-api-key', $raw);
        $this->assertStringNotContainsString('Authorization', $raw);
    }

    #[Test]
    public function the_application_log_never_contains_the_token(): void
    {
        config()->set('esb.auth.static_token', 'super-secret-api-key');
        Http::fake(['*' => Http::response($this->failure('EC03100032', 'Unknown Error'))]);

        $logFile = storage_path('logs/laravel.log');
        $before = file_exists($logFile) ? (string) file_get_contents($logFile) : '';

        try {
            $this->client()->get(EsbFamily::Core, '/product/list', operation: 'product.list');
        } catch (\Throwable) {
            // expected
        }

        $after = file_exists($logFile) ? (string) file_get_contents($logFile) : '';

        $this->assertStringNotContainsString('super-secret-api-key', substr($after, strlen($before)));
    }

    #[Test]
    public function each_call_carries_a_distinct_correlation_id(): void
    {
        Http::fake(['*' => Http::response($this->success())]);

        $this->client()->get(EsbFamily::Core, '/product/list', operation: 'product.list');
        $this->client()->get(EsbFamily::Core, '/product/list', operation: 'product.list');

        $ids = IntegrationLog::query()->pluck('correlation_id')->all();

        $this->assertCount(2, $ids);
        $this->assertCount(2, array_unique($ids));
    }

    #[Test]
    public function it_does_not_send_an_undocumented_correlation_header(): void
    {
        Http::fake(['*' => Http::response($this->success())]);

        $this->client()->get(EsbFamily::Core, '/product/list', operation: 'product.list');

        // The vendor documents no correlation or request ID header. Sending an
        // undocumented header is guesswork, and docs/05-api-integration.md
        // marks this TBD until the ESB team confirms one.
        Http::assertSent(function (Request $request): bool {
            foreach ($request->headers() as $name => $values) {
                if (str_contains(strtolower($name), 'correlation') || str_contains(strtolower($name), 'request-id')) {
                    return false;
                }
            }

            return true;
        });
    }

    #[Test]
    public function query_parameters_are_sent_on_a_read(): void
    {
        Http::fake(['*' => Http::response($this->success())]);

        $this->client()->get(
            EsbFamily::Core,
            '/product/list',
            query: ['page' => 2, 'limit' => 50],
            operation: 'product.list',
        );

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'page=2')
            && str_contains($request->url(), 'limit=50'));
    }
}
