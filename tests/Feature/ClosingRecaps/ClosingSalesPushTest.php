<?php

namespace Tests\Feature\ClosingRecaps;

use App\Audit\AuditAction;
use App\Authorization\Role;
use App\Jobs\PushSalesData;
use App\Models\ClosingItemMapping;
use App\Models\ClosingOutletMapping;
use App\Models\ClosingPaymentMapping;
use App\Models\ClosingRecap;
use App\Models\ClosingSalesPush;
use App\Models\SalesPush;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\ClosingTotals;
use Tests\TestCase;

/**
 * A closing recap's sales become ESB Push Sales Data V2 transactions
 * (ADR-032, P29-P30): one per visit purpose, prepared from the prices, taxes
 * and payments sync-api sent (contract revision 3, TBD), each payment's ERP
 * account mapped here, then queued through the sales push module (ADR-022).
 * Each visit purpose keeps its salesNum across revisions, which the ESB
 * overwrites.
 */
class ClosingSalesPushTest extends TestCase
{
    use HoldsClosingRecaps;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Http::fake();
        $this->seed(RolePermissionSeeder::class);
    }

    private function processor(): User
    {
        return $this->userWithRole(Role::IntegrationOperator);
    }

    private function mapSales(string $branchCode = 'STTSM'): void
    {
        ClosingOutletMapping::query()->create([
            'outlet_code' => 'STTSM', 'branch_id' => 12, 'branch_code' => $branchCode, 'branch_name' => 'Sushi Tei TSM',
            'location_id' => 31, 'location_name' => 'Gudang TSM',
        ]);
        ClosingItemMapping::query()->create([
            'source' => 'closing', 'item_key' => ClosingTotals::MENU_ID,
            'esb_menu_id' => 188, 'esb_menu_code' => 'SU001', 'esb_menu_name' => 'Sushi Roll Salmon',
            'bom_id' => 412, 'bom_name' => 'Sushi Roll Salmon',
        ]);
        ClosingItemMapping::query()->create([
            'source' => 'pos_only', 'item_key' => 'DRK-OCHA-01',
            'esb_menu_id' => 190, 'esb_menu_code' => 'OCHA', 'esb_menu_name' => 'Ocha Dingin',
        ]);
        $this->mapPayment('Cash', '1 1 01 01', 0);
        $this->mapPayment('QRIS', '1 1 02 05', 0.7);
    }

    private function mapPayment(string $method, string $coaNo, int|float $rate): void
    {
        ClosingPaymentMapping::query()->create([
            'payment_method' => $method, 'coa_no' => $coaNo, 'coa_name' => "Account {$coaNo}", 'merchant_discount_rate' => $rate,
        ]);
    }

    private function prepare(ClosingRecap $recap, ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->processor())->post(route('closing-recaps.sales.prepare', $recap));
    }

    private function send(ClosingRecap $recap, ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->processor())->post(route('closing-recaps.sales.send', $recap));
    }

    /**
     * @return array<string, ClosingSalesPush> The prepared drafts by visit purpose.
     */
    private function prepared(): array
    {
        return ClosingSalesPush::query()
            ->where('status', ClosingSalesPush::STATUS_PREPARED)
            ->orderBy('id')
            ->get()
            ->keyBy('visit_purpose_name')
            ->all();
    }

    // ================================================================ access

    #[Test]
    public function preparing_and_sending_sales_need_the_process_permission(): void
    {
        $recap = $this->holdRecap(ClosingTotals::revision3());
        $itAdmin = $this->userWithRole(Role::ItAdmin);

        $this->prepare($recap, $itAdmin)->assertForbidden();
        $this->send($recap, $itAdmin)->assertForbidden();

        $this->assertSame(0, ClosingSalesPush::query()->count());
    }

    // =============================================================== prepare

    #[Test]
    public function a_recap_without_sales_data_cannot_be_prepared_for_sales(): void
    {
        $this->mapSales();

        foreach ([$this->holdRecap(), $this->holdRecap(['outlet' => ['code' => 'STTSM'], 'productionDate' => '2026-10-13', 'sales' => []])] as $recap) {
            $this->prepare($recap)->assertSessionHasErrors([
                'sales' => 'sync-api sent no sales for this recap (contract revision 3).',
            ]);
        }

        $this->assertSame(0, ClosingSalesPush::query()->count());
    }

    #[Test]
    public function sales_need_the_outlet_and_every_sold_item_mapped(): void
    {
        $recap = $this->holdRecap(ClosingTotals::revision3());

        $this->prepare($recap)->assertSessionHasErrors('sales');

        $this->assertSame(0, ClosingSalesPush::query()->count());
    }

    #[Test]
    public function sales_wait_for_sync_api_to_send_the_payments(): void
    {
        $this->mapSales();
        $recap = $this->holdRecap(ClosingTotals::revision3(['sales' => [ClosingTotals::sales(['payments' => []])]]));

        $this->prepare($recap)->assertSessionHasErrors([
            'sales' => 'sync-api has not sent the payments of DINE IN yet.',
        ]);

        $this->assertSame(0, ClosingSalesPush::query()->count());
    }

    #[Test]
    public function every_payment_method_needs_its_erp_account(): void
    {
        $this->mapSales();
        $recap = $this->holdRecap(ClosingTotals::revision3(['sales' => [ClosingTotals::sales([
            'payments' => [ClosingTotals::payment(['paymentMethod' => 'GoPay'])],
        ])]]));

        $this->prepare($recap)->assertSessionHasErrors([
            'sales' => 'Payment method GoPay has no ERP account.',
        ]);

        $this->assertSame(0, ClosingSalesPush::query()->count());
    }

    #[Test]
    public function sales_do_not_wait_for_the_item_journal_mappings(): void
    {
        $this->mapSales(); // no purposes mapped: the item journals are not ready
        $recap = $this->holdRecap(ClosingTotals::revision3());

        $this->prepare($recap)->assertSessionHasNoErrors();

        $this->assertSame(1, ClosingSalesPush::query()->count());
    }

    #[Test]
    public function preparing_builds_one_push_sales_transaction_per_visit_purpose(): void
    {
        $this->mapSales();
        $recap = $this->holdRecap(ClosingTotals::revision3());

        $this->prepare($recap)->assertRedirect()->assertSessionHasNoErrors();

        $draft = ClosingSalesPush::query()->sole();
        $this->assertSame(ClosingSalesPush::STATUS_PREPARED, $draft->status);
        $this->assertSame(2, $draft->recap_sequence);
        $this->assertSame('DINE IN', $draft->visit_purpose_name);
        $this->assertSame(1, $draft->slot);
        // CL + branch + YYMMDD + slot: at most 19 of the ESB's 20 characters.
        $this->assertSame('CLSTTSM2610121', $draft->sales_num);

        $head = $draft->payload['salesHead'];
        $this->assertSame('CLSTTSM2610121', $head['salesNum']);
        $this->assertSame('CLSTTSM2610121', $head['billNum']);
        $this->assertSame('STTSM', $head['branchCode']);
        $this->assertSame('2026-10-12', $head['salesDate']);
        $this->assertSame('2026-10-12 23:59:59', $head['salesDateIn']);
        $this->assertSame('2026-10-12 23:59:59', $head['salesDateOut']);
        $this->assertSame('DINE IN', $head['visitPurposeName']);
        $this->assertSame(211000, $head['subtotal']);
        $this->assertSame(242650, $head['grandTotal']);
        $this->assertSame('', $head['memberCode']);
        $this->assertSame('Closing STTSM 2026-10-12 DINE IN, sequence 2', $head['additionalInfo']);

        $this->assertCount(2, $head['menu']);
        $this->assertSame(188, $head['menu'][0]['menuID']);
        $this->assertSame('SU001', $head['menu'][0]['menuCode']);
        $this->assertSame(13, $head['menu'][0]['qty']);
        $this->assertSame(15000, $head['menu'][0]['price']);
        $this->assertSame(224250, (int) $head['menu'][0]['total']);
        $this->assertArrayNotHasKey('visitPurposeName', $head['menu'][0]);
        $this->assertSame([], $head['menu'][0]['packages']);
        $this->assertSame('OCHA', $head['menu'][1]['menuCode']);
        $this->assertSame(2, $head['menu'][1]['qty']);

        // The account and MDR come from the gateway's payment method mapping.
        $this->assertCount(1, $head['payment']);
        $this->assertSame('Cash', $head['payment'][0]['paymentMethod']);
        $this->assertSame('1 1 01 01', $head['payment'][0]['coaNo']);
        $this->assertSame(0, $head['payment'][0]['merchantDiscountRate']);
        $this->assertSame(242650, $head['payment'][0]['paymentAmount']);
        $this->assertSame('', $head['payment'][0]['cardNumber']);

        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::ClosingSalesPrepared->value]);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function two_visit_purposes_are_two_transactions(): void
    {
        $this->mapSales();
        $recap = $this->holdRecap(ClosingTotals::twoPurposes());

        $this->prepare($recap)->assertSessionHasNoErrors();

        $drafts = $this->prepared();
        $this->assertSame(['DINE IN', 'TAKE AWAY'], array_keys($drafts));
        $this->assertSame('CLSTTSM2610121', $drafts['DINE IN']->sales_num);
        $this->assertSame('CLSTTSM2610122', $drafts['TAKE AWAY']->sales_num);

        $dineIn = $drafts['DINE IN']->payload['salesHead'];
        $this->assertSame(190900, $dineIn['grandTotal']);
        $this->assertSame(['SU001', 'OCHA'], array_column($dineIn['menu'], 'menuCode'));
        $this->assertSame([10, 2], array_column($dineIn['menu'], 'qty'));
        $this->assertSame('1 1 01 01', $dineIn['payment'][0]['coaNo']);

        $takeAway = $drafts['TAKE AWAY']->payload['salesHead'];
        $this->assertSame('TAKE AWAY', $takeAway['visitPurposeName']);
        $this->assertSame(51750, $takeAway['grandTotal']);
        $this->assertSame(['SU001'], array_column($takeAway['menu'], 'menuCode'));
        $this->assertSame([3], array_column($takeAway['menu'], 'qty'));
        $this->assertSame('QRIS', $takeAway['payment'][0]['paymentMethod']);
        $this->assertSame('1 1 02 05', $takeAway['payment'][0]['coaNo']);
        $this->assertSame(0.7, $takeAway['payment'][0]['merchantDiscountRate']);
    }

    #[Test]
    public function an_item_with_two_prices_is_two_menu_lines(): void
    {
        $this->mapSales();
        $recap = $this->holdRecap(ClosingTotals::revision3(['items' => [
            ClosingTotals::closingItem(['prices' => [ClosingTotals::price(10, 15000), ClosingTotals::price(3, 12000)]]),
        ]]));

        $this->prepare($recap)->assertSessionHasNoErrors();

        $menu = ClosingSalesPush::query()->sole()->payload['salesHead']['menu'];
        $this->assertSame([10, 3], array_column($menu, 'qty'));
        $this->assertSame([15000, 12000], array_column($menu, 'price'));
    }

    #[Test]
    public function preparing_again_replaces_the_unsent_drafts(): void
    {
        $this->mapSales();
        $recap = $this->holdRecap(ClosingTotals::twoPurposes());

        $this->prepare($recap);
        $this->prepare($recap)->assertSessionHasNoErrors();

        $this->assertSame(2, ClosingSalesPush::query()->where('status', ClosingSalesPush::STATUS_PREPARED)->count());
        $this->assertSame(2, ClosingSalesPush::query()->where('status', ClosingSalesPush::STATUS_SUPERSEDED)->count());
        $this->assertSame(['CLSTTSM2610121', 'CLSTTSM2610122'], array_values(array_map(fn (ClosingSalesPush $d): string => $d->sales_num, $this->prepared())));
    }

    // ================================================================== send

    #[Test]
    public function sending_queues_every_transaction_through_the_sales_push_module(): void
    {
        $this->mapSales();
        $recap = $this->holdRecap(ClosingTotals::twoPurposes());
        $this->prepare($recap);

        $this->send($recap)->assertRedirect()->assertSessionHasNoErrors();

        $pushes = SalesPush::query()->orderBy('id')->get();
        $this->assertSame(['CLSTTSM2610121', 'CLSTTSM2610122'], $pushes->pluck('sales_num')->all());
        $this->assertNull($pushes[0]->api_client_id);
        $this->assertSame('STTSM', $pushes[0]->branch_code);
        $this->assertSame(SalesPush::STATUS_QUEUED, $pushes[0]->status);
        $this->assertSame(51750, $pushes[1]->payload['salesHead']['grandTotal']);

        $drafts = ClosingSalesPush::query()->orderBy('id')->get();
        $this->assertSame([ClosingSalesPush::STATUS_SENT, ClosingSalesPush::STATUS_SENT], $drafts->pluck('status')->all());
        $this->assertTrue($drafts[1]->salesPush?->is($pushes[1]));

        Queue::assertPushed(PushSalesData::class, 2);
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::ClosingSalesSent->value]);

        // Nothing left to send.
        $this->send($recap)->assertSessionHasErrors('sales');
        Queue::assertPushed(PushSalesData::class, 2);

        // Nor to prepare: this version is in the ERP.
        $this->prepare($recap)->assertSessionHasErrors('sales');
    }

    #[Test]
    public function a_revision_keeps_each_visit_purpose_under_its_sales_number(): void
    {
        $this->mapSales();
        $recap = $this->holdRecap(ClosingTotals::revision3(['sequence' => 1]));
        $this->prepare($recap);
        $this->send($recap);

        // TAKE AWAY is new in the revision and comes first; DINE IN keeps 1.
        $revision = ClosingTotals::twoPurposes(['sequence' => 2]);
        $revision['sales'] = array_reverse($revision['sales']);
        $this->holdRecap($revision);

        $this->prepare($recap)->assertSessionHasNoErrors();
        $this->send($recap)->assertSessionHasNoErrors();

        $pushes = SalesPush::query()->orderBy('id')->get();
        $this->assertSame(['CLSTTSM2610121', 'CLSTTSM2610122', 'CLSTTSM2610121'], $pushes->pluck('sales_num')->all());
        $this->assertSame('TAKE AWAY', $pushes[1]->payload['salesHead']['visitPurposeName']);
        $this->assertSame(190900, $pushes[2]->payload['salesHead']['grandTotal']);
        Queue::assertPushed(PushSalesData::class, 3);
    }

    #[Test]
    public function a_visit_purpose_gone_from_the_revision_does_not_lend_its_number(): void
    {
        $this->mapSales();
        $recap = $this->holdRecap(ClosingTotals::twoPurposes(['sequence' => 1]));
        $this->prepare($recap);
        $this->send($recap);

        // Only TAKE AWAY is left: it keeps 2, and 1 stays DINE IN's.
        $this->holdRecap(ClosingTotals::revision3([
            'sequence' => 2,
            'sales' => [ClosingTotals::sales(['visitPurposeName' => 'TAKE AWAY'])],
            'items' => [
                ClosingTotals::closingItem(['prices' => [ClosingTotals::price(13, 15000, 'TAKE AWAY')]]),
                ClosingTotals::posOnlyItem(['prices' => [ClosingTotals::price(2, 8000, 'TAKE AWAY')]]),
            ],
        ]));
        $this->prepare($recap)->assertSessionHasNoErrors();

        $this->assertSame(['TAKE AWAY'], array_keys($this->prepared()));
        $this->assertSame('CLSTTSM2610122', $this->prepared()['TAKE AWAY']->sales_num);

        // The operator is told the ERP still holds DINE IN's earlier sale.
        $this->actingAs($this->processor())
            ->get(route('closing-recaps.show', $recap))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('sales.dropped', [['visitPurposeName' => 'DINE IN', 'salesNum' => 'CLSTTSM2610121']]));
    }

    #[Test]
    public function an_old_draft_is_not_sent_after_a_revision(): void
    {
        $this->mapSales();
        $recap = $this->holdRecap(ClosingTotals::revision3(['sequence' => 1]));
        $this->prepare($recap);
        $this->holdRecap(ClosingTotals::revision3(['sequence' => 2]));

        $this->send($recap)->assertSessionHasErrors('sales');

        Queue::assertNothingPushed();
    }

    // ================================================================ screen

    #[Test]
    public function the_detail_shows_each_transaction_and_its_push(): void
    {
        $this->mapSales();
        $recap = $this->holdRecap(ClosingTotals::twoPurposes());
        $this->prepare($recap);

        $this->actingAs($this->processor())
            ->get(route('closing-recaps.show', $recap))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('sales.hasData', true)
                ->where('sales.purposes', [
                    ['visitPurposeName' => 'DINE IN', 'grandTotal' => 190900, 'payments' => 1],
                    ['visitPurposeName' => 'TAKE AWAY', 'grandTotal' => 51750, 'payments' => 1],
                ])
                ->where('sales.missing', [])
                ->where('sales.dropped', [])
                ->has('sales.drafts', 2)
                ->where('sales.drafts.0.salesNum', 'CLSTTSM2610121')
                ->where('sales.drafts.0.visitPurposeName', 'DINE IN')
                ->where('sales.drafts.0.status', ClosingSalesPush::STATUS_PREPARED)
                ->where('sales.drafts.0.menuLines', 2)
                ->where('sales.drafts.0.push', null)
                ->where('sales.drafts.1.salesNum', 'CLSTTSM2610122')
                ->where('sales.drafts.1.grandTotal', 51750)
                ->where('actions.prepareSales', true)
                ->where('actions.sendSales', true));

        $this->send($recap);

        $this->actingAs($this->processor())
            ->get(route('closing-recaps.show', $recap))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('sales.drafts.0.status', ClosingSalesPush::STATUS_SENT)
                ->where('sales.drafts.0.push.status', SalesPush::STATUS_QUEUED)
                ->where('sales.drafts.1.push.id', SalesPush::query()->orderByDesc('id')->firstOrFail()->public_id)
                ->where('actions.prepareSales', false)
                ->where('actions.sendSales', false));
    }

    #[Test]
    public function the_detail_says_what_the_sales_still_wait_for(): void
    {
        $this->mapSales();
        $recap = $this->holdRecap(ClosingTotals::revision3(['sales' => [ClosingTotals::sales(['payments' => []])]]));

        $this->actingAs($this->processor())
            ->get(route('closing-recaps.show', $recap))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('sales.hasData', true)
                ->where('sales.missing.0.type', 'sales-payments')
                ->where('sales.missing.0.message', 'sync-api has not sent the payments of DINE IN yet.')
                ->where('actions.prepareSales', false));
    }

    #[Test]
    public function the_detail_says_when_sync_api_sent_no_sales_data(): void
    {
        $recap = $this->holdRecap();

        $this->actingAs($this->processor())
            ->get(route('closing-recaps.show', $recap))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('sales.hasData', false)
                ->where('sales.drafts', [])
                ->where('actions.prepareSales', false));
    }
}
