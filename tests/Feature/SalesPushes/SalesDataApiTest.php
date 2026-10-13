<?php

namespace Tests\Feature\SalesPushes;

use App\Jobs\PushSalesData;
use App\Models\ApiClient;
use App\Models\SalesPush;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\SalesDataV2;
use Tests\TestCase;

/**
 * POST /api/v1/sales-data -- a POS hands the gateway one transaction in the
 * ESB's Push Sales Data V2 shape. The gateway stores it, answers 202, and
 * pushes it to the ESB from the queue (ADR-022).
 */
class SalesDataApiTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    private ApiClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Http::fake();

        [$this->client, $this->token] = ApiClient::issue('pos-rxt');
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function push(array $body, ?string $token = null): TestResponse
    {
        return $this->withToken($token ?? $this->token)->postJson('/api/v1/sales-data', $body);
    }

    // ============================================================ authentication

    #[Test]
    public function a_request_without_a_token_is_unauthorized(): void
    {
        $this->postJson('/api/v1/sales-data', SalesDataV2::payload())->assertUnauthorized();

        Queue::assertNothingPushed();
        $this->assertSame(0, SalesPush::query()->count());
    }

    #[Test]
    public function an_unknown_or_revoked_token_is_unauthorized(): void
    {
        $this->push(SalesDataV2::payload(), 'not-a-token')->assertUnauthorized();

        $this->client->update(['is_active' => false]);
        $this->push(SalesDataV2::payload())->assertUnauthorized();

        Queue::assertNothingPushed();
    }

    #[Test]
    public function a_web_session_is_not_an_api_credential(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/v1/sales-data', SalesDataV2::payload())
            ->assertUnauthorized();
    }

    // ================================================================= accepted

    #[Test]
    public function a_transaction_is_stored_and_queued(): void
    {
        $response = $this->push(SalesDataV2::payload())
            ->assertAccepted()
            ->assertJsonPath('salesNum', 'SRXT172777428297')
            ->assertJsonPath('status', SalesPush::STATUS_QUEUED);

        $push = SalesPush::query()->sole();

        $response->assertJsonPath('id', $push->public_id);
        $this->assertSame($this->client->id, $push->api_client_id);
        $this->assertSame('SRXT172777428297', $push->sales_num);
        $this->assertSame('RXT', $push->branch_code);
        $this->assertSame('2024-10-01', $push->sales_date?->toDateString());

        // Forwarded unchanged later, so stored unchanged now: the empty
        // strings the ESB requires must not have become nulls.
        $this->assertSame(SalesDataV2::payload(), $push->payload);

        Queue::assertPushed(PushSalesData::class, fn (PushSalesData $job) => $job->salesPushId === $push->id);

        // Nothing reaches the ESB inside the request.
        Http::assertNothingSent();
    }

    #[Test]
    public function the_payload_is_encrypted_at_rest(): void
    {
        $this->push(SalesDataV2::payload(['customerName' => 'Budi Santoso']))->assertAccepted();

        $raw = (string) DB::table('sales_pushes')->value('payload');

        $this->assertStringNotContainsString('Budi Santoso', $raw);
        $this->assertStringNotContainsString('salesHead', $raw);
    }

    #[Test]
    public function only_the_sales_head_is_kept(): void
    {
        $this->push([...SalesDataV2::payload(), 'somethingElse' => 'x'])->assertAccepted();

        $this->assertSame(['salesHead'], array_keys(SalesPush::query()->sole()->payload));
    }

    // =============================================================== validation

    #[Test]
    public function the_identity_of_a_transaction_is_required(): void
    {
        $this->push(['salesHead' => ['menu' => [], 'payment' => []]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['salesHead.salesNum', 'salesHead.branchCode', 'salesHead.salesDateIn']);

        $this->push([])->assertUnprocessable()->assertJsonValidationErrors(['salesHead']);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function values_the_esb_bounds_are_bounded_here(): void
    {
        $this->push(SalesDataV2::payload(['salesNum' => str_repeat('S', 21)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['salesHead.salesNum']);

        $this->push(SalesDataV2::payload(['salesDateIn' => '01/10/2024']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['salesHead.salesDateIn']);

        $this->push(SalesDataV2::payload(['menu' => 'not-a-list', 'payment' => 'nope']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['salesHead.menu', 'salesHead.payment']);
    }

    #[Test]
    public function an_empty_sales_date_is_accepted_as_the_esb_allows(): void
    {
        $this->push(SalesDataV2::payload(['salesDate' => '']))->assertAccepted();

        $push = SalesPush::query()->sole();
        $this->assertNull($push->sales_date);
        $this->assertSame('', $push->payload['salesHead']['salesDate']);
    }

    #[Test]
    public function an_oversized_body_is_refused(): void
    {
        config()->set('sales_push.max_body_kb', 1);

        $this->push(SalesDataV2::payload(['additionalInfo' => str_repeat('x', 2048)]))->assertStatus(413);

        Queue::assertNothingPushed();
    }

    // =================================================================== status

    #[Test]
    public function a_client_can_read_back_the_status_of_its_own_push(): void
    {
        $id = $this->push(SalesDataV2::payload())->json('id');

        $this->withToken($this->token)
            ->getJson('/api/v1/sales-data/'.$id)
            ->assertOk()
            ->assertJsonPath('id', $id)
            ->assertJsonPath('status', SalesPush::STATUS_QUEUED)
            ->assertJsonPath('errors', [])
            ->assertJsonMissingPath('payload');
    }

    #[Test]
    public function a_client_cannot_read_another_clients_push(): void
    {
        $id = $this->push(SalesDataV2::payload())->json('id');

        [, $otherToken] = ApiClient::issue('pos-other');

        $this->withToken($otherToken)->getJson('/api/v1/sales-data/'.$id)->assertNotFound();
    }

    #[Test]
    public function the_api_is_rate_limited_per_client(): void
    {
        config()->set('sales_push.rate_limit_per_minute', 2);

        $this->push(SalesDataV2::payload())->assertAccepted();
        $this->push(SalesDataV2::payload())->assertAccepted();
        $this->push(SalesDataV2::payload())->assertTooManyRequests();

        [, $otherToken] = ApiClient::issue('pos-other');
        $this->push(SalesDataV2::payload(), $otherToken)->assertAccepted();
    }
}
