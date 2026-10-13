<?php

namespace Tests\Feature\SalesPushes;

use App\Integrations\ESB\Exceptions\EsbTimeoutException;
use App\Jobs\PushSalesData;
use App\Models\ApiClient;
use App\Models\SalesPush;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Fixtures\SalesDataV2;
use Tests\TestCase;

/**
 * The queued half of a sales push: send the stored transaction to the ESB and
 * record what it said.
 *
 * The ESB overwrites a transaction pushed again with the same salesNum, so a
 * push whose outcome is unknown may be repeated. A definite refusal is not
 * repeated: the POS has to fix the data.
 */
class PushSalesDataJobTest extends TestCase
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

    /**
     * @param  array<string, mixed>  $headOverrides
     */
    private function queuedPush(array $headOverrides = []): SalesPush
    {
        [$client] = ApiClient::query()->exists()
            ? [ApiClient::query()->first()]
            : ApiClient::issue('pos-rxt');

        return SalesPush::receive($client, SalesDataV2::payload($headOverrides));
    }

    private function runJob(SalesPush $push): void
    {
        app()->call([new PushSalesData($push->id, $push->sales_num), 'handle']);
    }

    #[Test]
    public function an_accepted_push_is_marked_succeeded(): void
    {
        Http::fake([self::URL => Http::response(SalesDataV2::successResponse())]);

        $push = $this->queuedPush();
        $this->runJob($push);

        $push->refresh();
        $this->assertSame(SalesPush::STATUS_SUCCEEDED, $push->status);
        $this->assertSame(1, $push->attempts);
        $this->assertSame('00', $push->esb_status);
        $this->assertSame('2024-10-01 16:42:46', $push->esb_sync_date);
        $this->assertNotNull($push->pushed_at);
        $this->assertSame([], $push->errors);

        Http::assertSent(fn (Request $request) => $request->data() === SalesDataV2::payload());
    }

    #[Test]
    public function a_refusal_is_final_and_keeps_the_esb_messages(): void
    {
        Http::fake([self::URL => Http::response(SalesDataV2::notFoundResponse(), 400)]);

        $push = $this->queuedPush();
        $this->runJob($push);

        $push->refresh();
        $this->assertSame(SalesPush::STATUS_REJECTED, $push->status);
        $this->assertSame('validation', $push->failure);
        $this->assertNotNull($push->correlation_id);
        $this->assertSame(
            [
                ['field' => 'salesHead.memberCode', 'message' => 'Member not found: salesHead.memberCode'],
                ['field' => 'salesHead.visitPurposeName', 'message' => 'Visit Purpose not found: salesHead.visitPurposeName'],
            ],
            $push->errors,
        );
    }

    #[Test]
    public function an_unknown_outcome_is_rethrown_so_the_queue_tries_again(): void
    {
        Http::fake([self::URL => fn () => throw new ConnectionException('cURL error 28: Operation timed out')]);

        $push = $this->queuedPush();

        try {
            $this->runJob($push);
            $this->fail('Expected the timeout to reach the queue.');
        } catch (EsbTimeoutException) {
            // The queue owns the retry and its backoff.
        }

        $push->refresh();
        $this->assertSame(SalesPush::STATUS_QUEUED, $push->status);
        $this->assertSame(1, $push->attempts);
        $this->assertSame('timeout', $push->failure);
        $this->assertNotNull($push->correlation_id);
    }

    #[Test]
    public function a_credential_the_esb_refuses_needs_a_person_not_a_retry(): void
    {
        Http::fake([self::URL => Http::response([
            'path' => self::URL,
            'code' => 401,
            'timestamp' => '2024-10-01 17:41:37',
            'message' => 'Your request was made with invalid credentials.',
        ], 401)]);

        $push = $this->queuedPush();
        $this->runJob($push);

        $push->refresh();
        $this->assertSame(SalesPush::STATUS_FAILED, $push->status);
        $this->assertSame('authentication', $push->failure);
    }

    #[Test]
    public function an_unconfigured_family_fails_without_calling_anyone(): void
    {
        Http::fake();
        config()->set('esb.families.fnb_backend.staging_int', null);

        $push = $this->queuedPush();
        $this->runJob($push);

        $push->refresh();
        $this->assertSame(SalesPush::STATUS_FAILED, $push->status);
        $this->assertSame('configuration', $push->failure);
        Http::assertNothingSent();
    }

    #[Test]
    public function running_out_of_attempts_marks_the_push_failed(): void
    {
        $push = $this->queuedPush();

        (new PushSalesData($push->id, $push->sales_num))->failed(new RuntimeException('gave up'));

        $this->assertSame(SalesPush::STATUS_FAILED, $push->fresh()->status);
    }

    #[Test]
    public function a_settled_push_is_not_sent_again(): void
    {
        Http::fake();

        $push = $this->queuedPush();
        $push->update(['status' => SalesPush::STATUS_SUCCEEDED]);

        $this->runJob($push);

        Http::assertNothingSent();
    }

    #[Test]
    public function an_older_push_never_overwrites_a_newer_one(): void
    {
        // The ESB keeps whatever arrives last for a salesNum. A retried older
        // version must not replace the newer one the POS sent since.
        Http::fake([self::URL => Http::response(SalesDataV2::successResponse())]);

        $older = $this->queuedPush(['grandTotal' => 1000]);
        $this->queuedPush(['grandTotal' => 2000]);

        $this->runJob($older);

        $this->assertSame(SalesPush::STATUS_SUPERSEDED, $older->fresh()->status);
        Http::assertNothingSent();
    }

    #[Test]
    public function a_rejected_newer_push_does_not_supersede_an_older_one(): void
    {
        Http::fake([self::URL => Http::response(SalesDataV2::successResponse())]);

        $older = $this->queuedPush();
        $this->queuedPush()->update(['status' => SalesPush::STATUS_REJECTED]);

        $this->runJob($older);

        $this->assertSame(SalesPush::STATUS_SUCCEEDED, $older->fresh()->status);
    }

    #[Test]
    public function pushes_of_one_transaction_do_not_run_side_by_side(): void
    {
        $push = $this->queuedPush();

        $middleware = (new PushSalesData($push->id, $push->sales_num))->middleware();

        $this->assertInstanceOf(WithoutOverlapping::class, $middleware[0]);
        $this->assertSame('sales-push:SRXT172777428297', $middleware[0]->key);
    }

    #[Test]
    public function the_retry_budget_is_bounded(): void
    {
        $job = new PushSalesData(1, 'S1');

        $this->assertSame((int) config('sales_push.tries'), $job->tries);
        $this->assertGreaterThan(0, $job->tries);
        $this->assertNotEmpty($job->backoff());
    }
}
