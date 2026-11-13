<?php

namespace Tests\Feature\ClosingRecaps;

use App\Audit\AuditAction;
use App\Authorization\Role;
use App\Jobs\PushSalesData;
use App\Models\ClosingItemMapping;
use App\Models\ClosingOutletMapping;
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
 * A closing recap's sales become one ESB Push Sales Data V2 transaction
 * (ADR-032, P29): prepared from the prices, taxes and payments sync-api sent
 * (contract revision 3, TBD), then queued through the sales push module
 * (ADR-022). A revision is pushed again under the same salesNum, which the
 * ESB overwrites.
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
    }

    private function prepare(ClosingRecap $recap, ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->processor())->post(route('closing-recaps.sales.prepare', $recap));
    }

    private function send(ClosingRecap $recap, ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->processor())->post(route('closing-recaps.sales.send', $recap));
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
        $recap = $this->holdRecap();

        $this->prepare($recap)->assertSessionHasErrors([
            'sales' => 'sync-api sent no prices, taxes or payments for this recap (contract revision 3).',
        ]);

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
    public function sales_do_not_wait_for_the_item_journal_mappings(): void
    {
        $this->mapSales(); // no purposes mapped: the item journals are not ready
        $recap = $this->holdRecap(ClosingTotals::revision3());

        $this->prepare($recap)->assertSessionHasNoErrors();

        $this->assertSame(1, ClosingSalesPush::query()->count());
    }

    #[Test]
    public function preparing_builds_one_push_sales_transaction(): void
    {
        $this->mapSales();
        $recap = $this->holdRecap(ClosingTotals::revision3());

        $this->prepare($recap)->assertRedirect()->assertSessionHasNoErrors();

        $draft = ClosingSalesPush::query()->sole();
        $this->assertSame(ClosingSalesPush::STATUS_PREPARED, $draft->status);
        $this->assertSame(2, $draft->recap_sequence);
        $this->assertSame('CLSTTSM20261012', $draft->sales_num);

        $head = $draft->payload['salesHead'];
        $this->assertSame('CLSTTSM20261012', $head['salesNum']);
        $this->assertSame('CLSTTSM20261012', $head['billNum']);
        $this->assertSame('STTSM', $head['branchCode']);
        $this->assertSame('2026-10-12', $head['salesDate']);
        $this->assertSame('2026-10-12 23:59:59', $head['salesDateIn']);
        $this->assertSame('2026-10-12 23:59:59', $head['salesDateOut']);
        $this->assertSame('DINE IN', $head['visitPurposeName']);
        $this->assertSame(211000, $head['subtotal']);
        $this->assertSame(242650, $head['grandTotal']);
        $this->assertSame('', $head['memberCode']);
        $this->assertSame('Closing STTSM 2026-10-12, sequence 2', $head['additionalInfo']);

        $this->assertCount(2, $head['menu']);
        $this->assertSame(188, $head['menu'][0]['menuID']);
        $this->assertSame('SU001', $head['menu'][0]['menuCode']);
        $this->assertSame(13, $head['menu'][0]['qty']);
        $this->assertSame(15000, $head['menu'][0]['price']);
        $this->assertSame(224250, (int) $head['menu'][0]['total']);
        $this->assertSame([], $head['menu'][0]['packages']);
        $this->assertSame([], $head['menu'][0]['extras']);
        $this->assertSame('OCHA', $head['menu'][1]['menuCode']);
        $this->assertSame(2, $head['menu'][1]['qty']);

        $this->assertSame('Cash', $head['payment'][0]['paymentMethod']);
        $this->assertSame(242650, $head['payment'][0]['paymentAmount']);
        $this->assertSame('', $head['payment'][0]['cardNumber']);

        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::ClosingSalesPrepared->value]);
        Queue::assertNothingPushed();
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
    public function preparing_again_replaces_an_unsent_draft(): void
    {
        $this->mapSales();
        $recap = $this->holdRecap(ClosingTotals::revision3());

        $this->prepare($recap);
        $this->prepare($recap)->assertSessionHasNoErrors();

        $this->assertSame(1, ClosingSalesPush::query()->where('status', ClosingSalesPush::STATUS_PREPARED)->count());
        $this->assertSame(1, ClosingSalesPush::query()->where('status', ClosingSalesPush::STATUS_SUPERSEDED)->count());
    }

    // ================================================================== send

    #[Test]
    public function sending_queues_the_transaction_through_the_sales_push_module(): void
    {
        $this->mapSales();
        $recap = $this->holdRecap(ClosingTotals::revision3());
        $this->prepare($recap);

        $this->send($recap)->assertRedirect()->assertSessionHasNoErrors();

        $push = SalesPush::query()->sole();
        $this->assertNull($push->api_client_id);
        $this->assertSame('CLSTTSM20261012', $push->sales_num);
        $this->assertSame('STTSM', $push->branch_code);
        $this->assertSame(SalesPush::STATUS_QUEUED, $push->status);
        $this->assertSame(242650, $push->payload['salesHead']['grandTotal']);

        $draft = ClosingSalesPush::query()->sole();
        $this->assertSame(ClosingSalesPush::STATUS_SENT, $draft->status);
        $this->assertTrue($draft->salesPush?->is($push));

        Queue::assertPushed(PushSalesData::class, 1);
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::ClosingSalesSent->value]);

        // Nothing left to send.
        $this->send($recap)->assertSessionHasErrors('sales');
        Queue::assertPushed(PushSalesData::class, 1);
    }

    #[Test]
    public function a_revision_is_pushed_again_under_the_same_sales_number(): void
    {
        $this->mapSales();
        $recap = $this->holdRecap(ClosingTotals::revision3(['sequence' => 1]));
        $this->prepare($recap);
        $this->send($recap);

        $this->holdRecap(ClosingTotals::revision3(['sequence' => 2, 'sales' => ClosingTotals::sales(['grandTotal' => 250000])]));
        $this->prepare($recap)->assertSessionHasNoErrors();
        $this->send($recap)->assertSessionHasNoErrors();

        $pushes = SalesPush::query()->orderBy('id')->get();
        $this->assertCount(2, $pushes);
        $this->assertSame(['CLSTTSM20261012', 'CLSTTSM20261012'], $pushes->pluck('sales_num')->all());
        $this->assertSame(250000, $pushes[1]->payload['salesHead']['grandTotal']);
        Queue::assertPushed(PushSalesData::class, 2);
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
    public function the_detail_shows_the_sales_transaction_and_its_push(): void
    {
        $this->mapSales();
        $recap = $this->holdRecap(ClosingTotals::revision3());
        $this->prepare($recap);

        $this->actingAs($this->processor())
            ->get(route('closing-recaps.show', $recap))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('sales.hasData', true)
                ->where('sales.grandTotal', 242650)
                ->where('sales.payments', 1)
                ->where('sales.draft.salesNum', 'CLSTTSM20261012')
                ->where('sales.draft.status', ClosingSalesPush::STATUS_PREPARED)
                ->where('sales.draft.menuLines', 2)
                ->where('sales.draft.push', null)
                ->where('actions.prepareSales', true)
                ->where('actions.sendSales', true));

        $this->send($recap);

        $this->actingAs($this->processor())
            ->get(route('closing-recaps.show', $recap))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('sales.draft.status', ClosingSalesPush::STATUS_SENT)
                ->where('sales.draft.push.status', SalesPush::STATUS_QUEUED)
                ->where('sales.draft.push.id', SalesPush::query()->sole()->public_id)
                ->where('actions.sendSales', false));
    }

    #[Test]
    public function the_detail_says_when_sync_api_sent_no_sales_data(): void
    {
        $recap = $this->holdRecap();

        $this->actingAs($this->processor())
            ->get(route('closing-recaps.show', $recap))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('sales.hasData', false)
                ->where('sales.draft', null)
                ->where('actions.prepareSales', false));
    }
}
