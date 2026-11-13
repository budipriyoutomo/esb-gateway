<?php

namespace Tests\Feature\ClosingRecaps;

use App\Models\ApiClient;
use App\Models\ClosingRecap;
use App\Models\ClosingRecapItem;
use App\Models\ClosingSubmission;
use App\Models\SalesPush;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\ClosingTotals;
use Tests\TestCase;

/**
 * POST /api/v1/closing-totals -- sync-api Maharasa hands the gateway one
 * closing recap per outlet and production date. The gateway only holds it:
 * nothing reaches the ESB on receipt (ADR-032).
 *
 * The scenario numbers refer to docs/spesifikasi-penerima-kirim-closing.md §8.
 */
class ClosingTotalsApiTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Http::fake();

        [, $this->token] = ApiClient::issue('sync-api', [ApiClient::SCOPE_CLOSING_TOTALS]);
    }

    /**
     * @param  array<string, mixed>|string  $body  An array is encoded as the sender does.
     * @param  array<string, string>  $headers
     */
    private function send(array|string $body, array $headers = [], ?string $token = null): TestResponse
    {
        $content = is_string($body) ? $body : ClosingTotals::encode($body);
        $messageId = is_array($body) ? (string) ($body['messageId'] ?? '') : ClosingTotals::MESSAGE_ID;

        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.($token ?? $this->token),
            'HTTP_IDEMPOTENCY_KEY' => $messageId,
        ];

        foreach ($headers as $name => $value) {
            $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
        }

        return $this->call('POST', '/api/v1/closing-totals', [], [], [], $server, $content);
    }

    private function messageId(int $n): string
    {
        return sprintf('00000000-0000-4000-8000-%012d', $n);
    }

    private function recap(): ClosingRecap
    {
        return ClosingRecap::query()->sole();
    }

    // ============================================================ authentication

    #[Test]
    public function a_wrong_token_is_unauthorized(): void // scenario 9
    {
        $this->send(ClosingTotals::payload(), token: 'not-a-token')
            ->assertUnauthorized();

        $this->assertSame(0, ClosingSubmission::query()->count());
    }

    #[Test]
    public function a_pos_token_cannot_send_closing_recaps(): void
    {
        [, $posToken] = ApiClient::issue('pos-rxt');

        $this->send(ClosingTotals::payload(), token: $posToken)
            ->assertForbidden()
            ->assertJsonPath('message', 'This API client may not call this endpoint.');

        $this->assertSame(0, ClosingSubmission::query()->count());
    }

    #[Test]
    public function a_closing_token_cannot_push_sales_data(): void
    {
        $this->withToken($this->token)
            ->postJson('/api/v1/sales-data', ['salesHead' => []])
            ->assertForbidden();

        $this->assertSame(0, SalesPush::query()->count());
    }

    #[Test]
    public function a_web_session_is_not_an_api_credential(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/v1/closing-totals', ClosingTotals::payload())
            ->assertUnauthorized();
    }

    // ================================================================= accepted

    #[Test]
    public function a_first_recap_is_held_and_answered_201(): void // scenario 1
    {
        $body = ClosingTotals::encode(ClosingTotals::payload());

        $response = $this->send($body)->assertCreated();

        $submission = ClosingSubmission::query()->sole();
        $response->assertJson([
            'id' => $submission->public_id,
            'recapId' => $this->recap()->public_id,
            'sequence' => 2,
            'items' => 2,
        ]);

        $this->assertSame(ClosingTotals::MESSAGE_ID, $submission->message_id);
        $this->assertSame('STTSM', $submission->outlet_code);
        $this->assertSame('2026-10-12', $submission->production_date->toDateString());
        $this->assertSame(2, $submission->sequence);
        $this->assertSame('2026-10-13 03:00:00', $submission->sent_at->utc()->toDateTimeString());
        $this->assertSame($body, $submission->body);
        $this->assertSame(hash('sha256', $body), $submission->body_hash);

        $recap = $this->recap();
        $this->assertSame('STTSM', $recap->outlet_code);
        $this->assertSame('2026-10-12', $recap->production_date->toDateString());
        $this->assertSame(2, $recap->sequence);
        $this->assertTrue($recap->submission->is($submission));

        $closing = $recap->items()->where('source', 'closing')->sole();
        $this->assertSame(ClosingTotals::MENU_ID, $closing->menu_id);
        $this->assertSame('SU-001', $closing->menu_code);
        $this->assertSame('Sushi Roll Salmon', $closing->menu_name);
        $this->assertNull($closing->product_code);
        $this->assertSame('10.000000', $closing->colorplate_sold);
        $this->assertSame('3.000000', $closing->pos_qty);
        $this->assertSame('13.000000', $closing->total_sold);
        $this->assertSame('1.000000', $closing->waste);
        $this->assertSame('-1.000000', $closing->adjustment);
        $this->assertSame('0.000000', $closing->compensation);

        $posOnly = $recap->items()->where('source', 'pos_only')->sole();
        $this->assertNull($posOnly->menu_id);
        $this->assertSame('456', $posOnly->product_id);
        $this->assertSame('DRK-OCHA-01', $posOnly->product_code);
        $this->assertSame('Ocha Dingin', $posOnly->product_name);
    }

    #[Test]
    public function receiving_a_recap_never_calls_the_esb(): void
    {
        $this->send(ClosingTotals::payload())->assertCreated();

        Http::assertNothingSent();
        Queue::assertNothingPushed();
    }

    #[Test]
    public function the_outlet_code_is_matched_trimmed_and_upper_cased(): void
    {
        $this->send(ClosingTotals::payload([
            'messageId' => $this->messageId(1),
            'sequence' => 1,
            'outlet' => ['code' => ' sttsm '],
        ]))->assertCreated();

        $this->send(ClosingTotals::payload([
            'messageId' => $this->messageId(2),
            'sequence' => 2,
        ]))->assertCreated();

        $this->assertSame('STTSM', $this->recap()->outlet_code);
        $this->assertSame(2, $this->recap()->sequence);
    }

    #[Test]
    public function outlets_and_dates_are_held_separately(): void
    {
        $this->send(ClosingTotals::payload(['messageId' => $this->messageId(1)]))->assertCreated();
        $this->send(ClosingTotals::payload([
            'messageId' => $this->messageId(2),
            'outlet' => ['code' => 'RXT'],
        ]))->assertCreated();
        $this->send(ClosingTotals::payload([
            'messageId' => $this->messageId(3),
            'productionDate' => '2026-10-13',
        ]))->assertCreated();

        $this->assertSame(3, ClosingRecap::query()->count());
        $this->assertSame(6, ClosingRecapItem::query()->count());
    }

    #[Test]
    public function a_recap_of_pos_only_items_is_accepted(): void // scenario 7
    {
        $this->send(ClosingTotals::payload(['items' => [ClosingTotals::posOnlyItem()]]))
            ->assertCreated();

        $this->assertSame(['pos_only'], $this->recap()->items()->pluck('source')->all());
    }

    #[Test]
    public function fractions_and_negative_adjustments_are_kept_exactly(): void // scenario 8
    {
        $this->send(ClosingTotals::payload(['items' => [
            ClosingTotals::closingItem(['colorplateSold' => 0.1, 'posQty' => 2.25, 'totalSold' => 2.35, 'adjustment' => -1.125]),
        ]]))->assertCreated();

        $item = $this->recap()->items()->sole();
        $this->assertSame('0.100000', $item->colorplate_sold);
        $this->assertSame('2.250000', $item->pos_qty);
        $this->assertSame('2.350000', $item->total_sold);
        $this->assertSame('-1.125000', $item->adjustment);
    }

    #[Test]
    public function non_ascii_names_are_stored_intact(): void // scenario 12
    {
        $this->send(ClosingTotals::payload(['items' => [
            ClosingTotals::closingItem(['menuName' => 'Sushi Tamago 卵 — Spésial']),
        ]]))->assertCreated();

        $this->assertSame('Sushi Tamago 卵 — Spésial', $this->recap()->items()->sole()->menu_name);
        $this->assertStringContainsString('卵', ClosingSubmission::query()->sole()->body);
    }

    // ============================================================== idempotency

    #[Test]
    public function the_same_message_again_is_a_409_duplicate_without_new_rows(): void // scenario 2, 11
    {
        $body = ClosingTotals::encode(ClosingTotals::payload());

        $first = $this->send($body)->assertCreated();

        $this->send($body)
            ->assertStatus(409)
            ->assertJson([
                'reason' => 'duplicate_message',
                'id' => $first->json('id'),
            ]);

        $this->assertSame(1, ClosingSubmission::query()->count());
        $this->assertSame(1, ClosingRecap::query()->count());
        $this->assertSame(2, ClosingRecapItem::query()->count());
    }

    #[Test]
    public function a_known_message_with_a_different_body_is_refused_not_hidden_as_a_duplicate(): void
    {
        $this->send(ClosingTotals::payload())->assertCreated();

        $this->send(ClosingTotals::payload(['items' => [ClosingTotals::posOnlyItem()]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['messageId']);

        $this->assertSame(2, ClosingRecapItem::query()->count());
    }

    #[Test]
    public function a_unique_violation_on_the_first_recap_is_retried_not_answered_409(): void // scenario 3
    {
        // Simulates a second request creating the same outlet/date row between
        // this request's read and its insert: the first insert attempt fails.
        $raced = false;
        ClosingRecap::creating(function (ClosingRecap $recap) use (&$raced): void {
            if (! $raced) {
                $raced = true;
                DB::table('closing_recaps')->insert([
                    'public_id' => (string) str()->ulid(),
                    'outlet_code' => $recap->outlet_code,
                    // The stored form, so the unique key really collides.
                    'production_date' => $recap->getAttributes()['production_date'],
                    'sequence' => 0,
                    'status' => ClosingRecap::STATUS_RECEIVED,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $this->send(ClosingTotals::payload())->assertCreated();

        $this->assertTrue($raced);
        $this->assertSame(2, $this->recap()->sequence);
        $this->assertSame(1, ClosingSubmission::query()->count());
    }

    // ================================================================= sequence

    #[Test]
    public function a_newer_sequence_replaces_every_item(): void // scenario 4
    {
        $this->send(ClosingTotals::payload(['messageId' => $this->messageId(1), 'sequence' => 1]))
            ->assertCreated();

        $this->send(ClosingTotals::payload([
            'messageId' => $this->messageId(2),
            'sequence' => 2,
            'items' => [ClosingTotals::closingItem(['colorplateSold' => 20, 'totalSold' => 23])],
        ]))->assertCreated()->assertJson(['sequence' => 2, 'items' => 1]);

        $recap = $this->recap();
        $this->assertSame(2, $recap->sequence);
        $this->assertSame(['closing'], $recap->items()->pluck('source')->all());
        $this->assertSame('23.000000', $recap->items()->sole()->total_sold);
        $this->assertSame($this->messageId(2), $recap->submission->message_id);

        // Every submission is kept for audit.
        $this->assertSame(2, ClosingSubmission::query()->count());
    }

    #[Test]
    public function an_older_sequence_is_a_409_stale_and_changes_nothing(): void // scenario 5
    {
        $this->send(ClosingTotals::payload(['messageId' => $this->messageId(2), 'sequence' => 2]))
            ->assertCreated();

        $this->send(ClosingTotals::payload([
            'messageId' => $this->messageId(1),
            'sequence' => 1,
            'items' => [ClosingTotals::posOnlyItem()],
        ]))
            ->assertStatus(409)
            ->assertJson(['reason' => 'stale_sequence', 'currentSequence' => 2]);

        $this->assertSame(2, $this->recap()->sequence);
        $this->assertSame(2, $this->recap()->items()->count());
        $this->assertSame(1, ClosingSubmission::query()->count());
    }

    #[Test]
    public function the_same_sequence_with_another_message_is_stale(): void
    {
        $this->send(ClosingTotals::payload(['messageId' => $this->messageId(1)]))->assertCreated();

        $this->send(ClosingTotals::payload(['messageId' => $this->messageId(2)]))
            ->assertStatus(409)
            ->assertJsonPath('reason', 'stale_sequence');
    }

    #[Test]
    public function a_gap_in_sequence_is_accepted(): void // scenario 6
    {
        $this->send(ClosingTotals::payload(['messageId' => $this->messageId(1), 'sequence' => 1]))
            ->assertCreated();

        $this->send(ClosingTotals::payload(['messageId' => $this->messageId(3), 'sequence' => 3]))
            ->assertCreated();

        $this->assertSame(3, $this->recap()->sequence);
    }

    // =============================================================== validation

    #[Test]
    public function missing_or_malformed_top_level_fields_are_named(): void
    {
        $this->send(['messageId' => 'not-a-uuid', 'sentAt' => '2026-10-13 03:00:00', 'sequence' => 0, 'productionDate' => '12/10/2026'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['messageId', 'sentAt', 'sequence', 'outlet.code', 'productionDate', 'items']);

        $this->assertSame(0, ClosingSubmission::query()->count());
    }

    #[Test]
    public function an_empty_item_list_is_refused(): void
    {
        $this->send(ClosingTotals::payload(['items' => []]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    #[Test]
    public function an_unknown_source_is_refused(): void
    {
        $this->send(ClosingTotals::payload(['items' => [ClosingTotals::closingItem(['source' => 'manual'])]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.source']);
    }

    #[Test]
    public function a_closing_item_needs_its_menu_id(): void
    {
        $this->send(ClosingTotals::payload(['items' => [ClosingTotals::closingItem(['menuId' => null])]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.menuId']);
    }

    #[Test]
    public function a_pos_only_item_needs_its_product_id_and_code(): void // scenario 10
    {
        $this->send(ClosingTotals::payload(['items' => [
            ClosingTotals::posOnlyItem(['productId' => null, 'productCode' => null]),
        ]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.productId', 'items.0.productCode']);
    }

    #[Test]
    public function an_item_key_may_appear_only_once(): void
    {
        $this->send(ClosingTotals::payload(['items' => [
            ClosingTotals::closingItem(),
            ClosingTotals::closingItem(),
            ClosingTotals::posOnlyItem(),
            ClosingTotals::posOnlyItem(['productId' => '789']),
            ClosingTotals::posOnlyItem(['productCode' => 'OTHER']),
        ]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.1.menuId', 'items.3.productCode', 'items.4.productId']);
    }

    #[Test]
    public function quantities_must_be_numbers_with_at_most_six_decimals(): void
    {
        $this->send(ClosingTotals::payload(['items' => [
            ClosingTotals::closingItem(['waste' => 'one', 'compensation' => null, 'posQty' => 1.1234567, 'totalSold' => 11.1234567]),
        ]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.waste', 'items.0.compensation', 'items.0.posQty']);
    }

    #[Test]
    public function total_sold_must_be_colorplate_plus_pos_quantity(): void
    {
        $this->send(ClosingTotals::payload(['items' => [ClosingTotals::closingItem(['totalSold' => 12])]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.totalSold']);
    }

    #[Test]
    public function the_idempotency_key_must_match_the_message_id(): void
    {
        $this->send(ClosingTotals::payload(), ['Idempotency-Key' => $this->messageId(9)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['Idempotency-Key']);

        $this->send(ClosingTotals::payload(), ['Idempotency-Key' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['Idempotency-Key']);
    }

    #[Test]
    public function an_oversized_body_is_refused(): void
    {
        config()->set('closing_totals.max_body_kb', 1);

        $this->send(ClosingTotals::payload(['items' => array_fill(0, 20, ClosingTotals::closingItem())]))
            ->assertStatus(413);
    }

    #[Test]
    public function a_body_that_is_not_json_is_refused(): void
    {
        $this->send('{not json')->assertUnprocessable();
    }

    // ============================================ revision 3: sales data (TBD)

    #[Test]
    public function sales_data_is_held_when_the_sender_sends_it(): void
    {
        $this->send(ClosingTotals::revision3())->assertCreated();

        $recap = $this->recap();
        $this->assertSame(242650, $recap->sales['grandTotal']);
        $this->assertSame('DINE IN', $recap->sales['visitPurposeName']);
        $this->assertSame('Cash', $recap->payments[0]['paymentMethod']);

        $closing = $recap->items()->where('source', 'closing')->sole();
        $this->assertSame(13, $closing->prices[0]['qty']);
        $this->assertSame(15000, $closing->prices[0]['price']);
    }

    #[Test]
    public function a_recap_without_sales_data_is_still_accepted(): void
    {
        $this->send(ClosingTotals::payload())->assertCreated();

        $this->assertNull($this->recap()->sales);
        $this->assertNull($this->recap()->payments);
        $this->assertNull($this->recap()->items()->firstOrFail()->prices);
    }

    #[Test]
    public function sales_and_payments_come_together(): void
    {
        $payload = ClosingTotals::revision3();
        unset($payload['payments']);

        $this->send($payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payments']);
    }

    #[Test]
    public function every_item_sold_needs_prices_adding_up_to_total_sold(): void
    {
        $this->send(ClosingTotals::revision3(['items' => [
            ClosingTotals::closingItem(['prices' => [ClosingTotals::price(10, 15000), ClosingTotals::price(2, 12000)]]),
            ClosingTotals::posOnlyItem(),
        ]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.prices', 'items.1.prices']);
    }

    #[Test]
    public function one_menu_may_be_sold_at_two_prices(): void
    {
        $this->send(ClosingTotals::revision3(['items' => [
            ClosingTotals::closingItem(['prices' => [ClosingTotals::price(10, 15000), ClosingTotals::price(3, 12000)]]),
            ClosingTotals::posOnlyItem(['prices' => [ClosingTotals::price(2, 8000)]]),
        ]]))->assertCreated();

        $this->assertCount(2, $this->recap()->items()->where('source', 'closing')->sole()->prices);
    }

    #[Test]
    public function price_lines_and_payments_need_their_numbers(): void
    {
        $this->send(ClosingTotals::revision3([
            'sales' => ClosingTotals::sales(['grandTotal' => 'a lot']),
            'payments' => [ClosingTotals::payment(['paymentAmount' => null, 'paymentMethod' => ''])],
            'items' => [ClosingTotals::closingItem(['prices' => [['qty' => 13]]])],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'sales.grandTotal',
                'payments.0.paymentAmount',
                'payments.0.paymentMethod',
                'items.0.prices.0.price',
                'items.0.prices.0.total',
            ]);
    }

    // =============================================================== rate limit

    #[Test]
    public function too_many_requests_answer_429_with_retry_after(): void
    {
        config()->set('closing_totals.rate_limit_per_minute', 1);

        $this->send(ClosingTotals::payload(['messageId' => $this->messageId(1)]))->assertCreated();

        $this->send(ClosingTotals::payload(['messageId' => $this->messageId(2)]))
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }
}
