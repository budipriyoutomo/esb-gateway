<?php

namespace Tests\Feature\ClosingRecaps;

use App\Audit\AuditAction;
use App\Authorization\Role;
use App\Models\AuditLog;
use App\Models\ClosingItemJournal;
use App\Models\ClosingItemMapping;
use App\Models\ClosingOutletMapping;
use App\Models\ClosingPurposeMapping;
use App\Models\ClosingRecap;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\ClosingTotals;
use Tests\Fixtures\CoreBom;
use Tests\Fixtures\CoreInventory;
use Tests\TestCase;

/**
 * A closing recap's waste, adjustment and compensation become item journals
 * in the ERP (ADR-032, P28): prepared from the current version through each
 * menu's BOM (one level), then sent by an operator. A revision deletes the
 * journals sent before and creates the new ones.
 *
 * BOM 412 (CoreBom): 200 Beras (detail 801) and 1.5 Telur (detail 811) per
 * menu. The fixture recap's menu has waste 1 and adjustment -1.
 */
class ClosingItemJournalTest extends TestCase
{
    use HoldsClosingRecaps;
    use RefreshDatabase;

    private const ESB = 'https://stg7.esb.co.id/core-int';

    /** @var list<array{method: string, url: string, body: array<string, mixed>}> */
    private array $sent = [];

    /** @var list<callable(Request): mixed> */
    private array $writes = [];

    /** @var (callable(Request): mixed)|null */
    private $onGetJournal = null;

    /** @var (callable(Request): mixed)|null */
    private $onBom = null;

    private int $created = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seed(RolePermissionSeeder::class);

        config()->set('esb.environment', 'staging_int');
        config()->set('esb.auth.mechanism', 'static_token');
        config()->set('esb.auth.static_token', 'a-static-api-key');
        config()->set('esb.retry.base_delay_ms', 0);

        // One fake for the whole test: Http::fake() stacks callbacks and runs
        // every one of them, so each scenario changes the properties instead.
        Http::fake(fn (Request $request) => $this->respond($request));
    }

    private function processor(): User
    {
        return $this->userWithRole(Role::IntegrationOperator);
    }

    private function mapEverything(): void
    {
        ClosingOutletMapping::query()->create([
            'outlet_code' => 'STTSM', 'branch_id' => 12, 'branch_code' => 'STTSM', 'branch_name' => 'Sushi Tei TSM',
            'location_id' => 31, 'location_name' => 'Gudang TSM',
        ]);
        ClosingItemMapping::query()->create([
            'source' => 'closing', 'item_key' => ClosingTotals::MENU_ID,
            'esb_menu_id' => 188, 'esb_menu_code' => 'SU001', 'esb_menu_name' => 'Sushi Roll Salmon',
            'bom_id' => 412, 'bom_name' => 'Nasi Goreng Spesial',
        ]);
        ClosingItemMapping::query()->create([
            'source' => 'pos_only', 'item_key' => 'DRK-OCHA-01',
            'esb_menu_id' => 190, 'esb_menu_code' => 'OCHA', 'esb_menu_name' => 'Ocha Dingin',
        ]);
        ClosingPurposeMapping::query()->create(['kind' => 'waste', 'purpose_id' => 7, 'purpose_name' => 'Waste', 'direction' => -1]);
        ClosingPurposeMapping::query()->create(['kind' => 'adjustment', 'purpose_id' => 8, 'purpose_name' => 'Adjustment', 'direction' => 1]);
        ClosingPurposeMapping::query()->create(['kind' => 'compensation', 'purpose_id' => 9, 'purpose_name' => 'Kompensasi', 'direction' => -1]);
    }

    /**
     * Answer BOM reads with BOM 412, item journal reads with a journal, and
     * each item journal write with $writes in order (the last one repeats).
     * Without $writes a create answers IJ-NEW-<n>.
     *
     * @param  list<callable(Request): mixed>  $writes
     */
    private function fakeErp(array $writes = [], ?callable $onGetJournal = null, ?callable $onBom = null): void
    {
        $this->sent = [];
        $this->created = 0;
        $this->writes = $writes;
        $this->onGetJournal = $onGetJournal;
        $this->onBom = $onBom;
    }

    private function respond(Request $request): mixed
    {
        $this->sent[] = ['method' => $request->method(), 'url' => $request->url(), 'body' => $request->data()];

        if ($request->method() === 'GET' && str_contains($request->url(), '/product/bom/')) {
            return $this->onBom ? ($this->onBom)($request) : Http::response(CoreBom::detailResponse());
        }

        if ($request->method() === 'GET' && str_contains($request->url(), '/inventory/item-journal/')) {
            return $this->onGetJournal ? ($this->onGetJournal)($request) : Http::response(CoreInventory::ijDetail());
        }

        $write = count($this->writes) > 1 ? array_shift($this->writes) : ($this->writes[0] ?? null);

        if ($write !== null) {
            return $write($request);
        }

        if ($request->method() !== 'POST') {
            return Http::response(CoreInventory::writeEnvelope());
        }

        $this->created++;

        return Http::response(CoreInventory::writeEnvelope(result: ['itemJournalNum' => 'IJ-NEW-'.$this->created]));
    }

    /** EC03100004 as the Core answers an unknown id (see BillOfMaterialTest). */
    private static function notFound(string $path): mixed
    {
        return Http::response(CoreInventory::notFoundResponse($path), 400);
    }

    /**
     * @return list<array{method: string, url: string, body: array<string, mixed>}>
     */
    private function journalCalls(): array
    {
        return array_values(array_filter($this->sent, fn (array $call): bool => str_contains($call['url'], '/inventory/item-journal')));
    }

    private function prepare(ClosingRecap $recap, ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->processor())->post(route('closing-recaps.prepare', $recap));
    }

    private function send(ClosingRecap $recap, ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->processor())->post(route('closing-recaps.send', $recap));
    }

    /**
     * @return list<array{productDetailId: int, qty: string}>
     */
    private static function lines(ClosingItemJournal $journal): array
    {
        return array_map(
            fn (array $line): array => ['productDetailId' => $line['productDetailId'], 'qty' => $line['qty']],
            $journal->lines,
        );
    }

    // ================================================================ access

    #[Test]
    public function preparing_and_sending_need_the_process_permission_and_esb_invoke(): void
    {
        $recap = $this->holdRecap();
        $itAdmin = $this->userWithRole(Role::ItAdmin);

        $this->prepare($recap, $itAdmin)->assertForbidden();
        $this->send($recap, $itAdmin)->assertForbidden();

        Http::assertNothingSent();
    }

    // =============================================================== prepare

    #[Test]
    public function a_recap_with_missing_mappings_cannot_be_prepared(): void
    {
        $recap = $this->holdRecap();

        $this->prepare($recap)->assertSessionHasErrors('recap');

        $this->assertSame(0, ClosingItemJournal::query()->count());
        Http::assertNothingSent();
    }

    #[Test]
    public function preparing_expands_each_kind_through_the_bom_with_its_direction(): void
    {
        $this->fakeErp();
        $this->mapEverything();
        $recap = $this->holdRecap();

        $this->prepare($recap)->assertRedirect()->assertSessionHasNoErrors();

        // The BOM was read from the ERP, once.
        $this->assertCount(1, array_filter($this->sent, fn (array $c): bool => str_ends_with($c['url'], '/product/bom/412')));

        $journals = ClosingItemJournal::query()->orderBy('id')->get();
        $this->assertSame(['waste', 'adjustment'], $journals->pluck('kind')->all()); // compensation is zero

        [$waste, $adjustment] = $journals;
        $this->assertSame(ClosingItemJournal::STATUS_PREPARED, $waste->status);
        $this->assertSame(2, $waste->recap_sequence);
        $this->assertSame(7, $waste->purpose_id);
        $this->assertSame(12, $waste->branch_id);
        $this->assertSame(31, $waste->location_id);
        $this->assertSame('2026-10-12', $waste->journal_date->toDateString());
        // waste 1 x direction -1 x BOM qty
        $this->assertSame([['productDetailId' => 801, 'qty' => '-200'], ['productDetailId' => 811, 'qty' => '-1.5']], self::lines($waste));
        $this->assertSame('Beras', $waste->lines[0]['productName']);
        $this->assertSame('Gram', $waste->lines[0]['uomName']);
        // adjustment -1 x direction +1 x BOM qty
        $this->assertSame([['productDetailId' => 801, 'qty' => '-200'], ['productDetailId' => 811, 'qty' => '-1.5']], self::lines($adjustment));

        // What the lines came from, kept as the BOM read at the time.
        $this->assertSame('Sushi Roll Salmon', $waste->breakdown[0]['item']);
        $this->assertSame('1', $waste->breakdown[0]['quantity']);
        $this->assertSame(412, $waste->breakdown[0]['bomId']);
        $this->assertSame('200', $waste->breakdown[0]['materials'][0]['perUnit']);

        $this->assertSame(ClosingRecap::STATUS_PREPARED, $recap->fresh()?->status);
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::ClosingRecapPrepared->value]);
    }

    #[Test]
    public function the_same_material_from_several_menus_is_summed(): void
    {
        $this->fakeErp();
        $this->mapEverything();
        $other = '11111111-2222-4333-8444-555555555555';
        ClosingItemMapping::query()->create(['source' => 'closing', 'item_key' => $other, 'bom_id' => 412, 'bom_name' => 'Nasi Goreng Spesial']);

        $recap = $this->holdRecap(['items' => [
            ClosingTotals::closingItem(['waste' => 1, 'adjustment' => 0]),
            ClosingTotals::closingItem(['menuId' => $other, 'menuName' => 'Sushi Tuna', 'colorplateSold' => 0, 'posQty' => 0, 'totalSold' => 0, 'waste' => 2.5, 'adjustment' => 0]),
        ]]);

        $this->prepare($recap)->assertSessionHasNoErrors();

        $waste = ClosingItemJournal::query()->sole();
        $this->assertSame([['productDetailId' => 801, 'qty' => '-700'], ['productDetailId' => 811, 'qty' => '-5.25']], self::lines($waste));
        $this->assertCount(2, $waste->breakdown);
    }

    #[Test]
    public function a_bom_the_erp_does_not_know_stops_the_preparation(): void
    {
        $this->fakeErp(onBom: fn () => self::notFound('/product/bom/412'));
        $this->mapEverything();
        $recap = $this->holdRecap();

        $this->prepare($recap)->assertSessionHasErrors(['recap' => 'The ERP has no bill of material 412 (Nasi Goreng Spesial).']);

        $this->assertSame(0, ClosingItemJournal::query()->count());
        $this->assertSame(ClosingRecap::STATUS_RECEIVED, $recap->fresh()?->status);
    }

    #[Test]
    public function preparing_again_replaces_the_unsent_drafts(): void
    {
        $this->fakeErp();
        $this->mapEverything();
        $recap = $this->holdRecap();

        $this->prepare($recap);
        $this->prepare($recap)->assertSessionHasNoErrors();

        $this->assertSame(2, ClosingItemJournal::query()->where('status', ClosingItemJournal::STATUS_PREPARED)->count());
        $this->assertSame(2, ClosingItemJournal::query()->where('status', ClosingItemJournal::STATUS_SUPERSEDED)->count());
    }

    // ================================================================== send

    #[Test]
    public function nothing_prepared_means_nothing_to_send(): void
    {
        $this->mapEverything();
        $recap = $this->holdRecap();

        $this->send($recap)->assertSessionHasErrors('recap');

        Http::assertNothingSent();
    }

    #[Test]
    public function sending_creates_one_item_journal_per_kind(): void
    {
        $this->fakeErp();
        $this->mapEverything();
        $recap = $this->holdRecap();
        $this->prepare($recap);
        $this->sent = [];

        $this->send($recap)->assertRedirect()->assertSessionHasNoErrors();

        $calls = $this->journalCalls();
        $this->assertCount(2, $calls);
        $this->assertSame('POST', $calls[0]['method']);
        $this->assertSame(self::ESB.'/inventory/item-journal', $calls[0]['url']);
        $this->assertSame([
            'itemJournalDate' => '2026-10-12',
            'branchID' => 12,
            'locationID' => 31,
            'additionalInfo' => 'Closing STTSM 2026-10-12, sequence 2: Waste',
            'itemJournalDetails' => [
                ['productDetailID' => 801, 'qty' => -200, 'purposeID' => 7],
                ['productDetailID' => 811, 'qty' => -1.5, 'purposeID' => 7],
            ],
        ], $calls[0]['body']);
        $this->assertSame(8, $calls[1]['body']['itemJournalDetails'][0]['purposeID']);

        $journals = ClosingItemJournal::query()->orderBy('id')->get();
        $this->assertSame([ClosingItemJournal::STATUS_CREATED, ClosingItemJournal::STATUS_CREATED], $journals->pluck('status')->all());
        $this->assertSame(['IJ-NEW-1', 'IJ-NEW-2'], $journals->pluck('item_journal_num')->all());
        $this->assertSame(ClosingRecap::STATUS_SENT, $recap->fresh()?->status);
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::ClosingRecapSent->value]);

        // Sending again sends nothing: every journal is created.
        $this->sent = [];
        $this->send($recap)->assertSessionHasErrors('recap');
        $this->assertSame([], $this->journalCalls());
    }

    #[Test]
    public function a_refused_journal_is_failed_and_the_others_still_go(): void
    {
        $this->fakeErp([
            fn () => Http::response([
                ...CoreInventory::writeEnvelope('EC03100003', 'Validation Error', 'fail'),
                'errors' => [['attribute' => 'itemJournalDate', 'code' => 'EC03100003', 'message' => 'Must be after the active stock period']],
            ], 400),
            fn () => Http::response(CoreInventory::writeEnvelope(result: ['itemJournalNum' => 'IJ-OK'])),
        ]);
        $this->mapEverything();
        $recap = $this->holdRecap();
        $this->prepare($recap);

        $this->send($recap)->assertRedirect();

        [$waste, $adjustment] = ClosingItemJournal::query()->where('status', '!=', ClosingItemJournal::STATUS_SUPERSEDED)->orderBy('id')->get();
        $this->assertSame(ClosingItemJournal::STATUS_FAILED, $waste->status);
        $this->assertSame('Must be after the active stock period', $waste->errors[0]['message']);
        $this->assertSame(ClosingItemJournal::STATUS_CREATED, $adjustment->status);
        $this->assertSame(ClosingRecap::STATUS_ATTENTION, $recap->fresh()?->status);

        // A failed journal is sent again; the created one is not.
        $this->fakeErp();
        $this->send($recap)->assertSessionHasNoErrors();
        $this->assertCount(1, $this->journalCalls());
        $this->assertSame(ClosingRecap::STATUS_SENT, $recap->fresh()?->status);
    }

    #[Test]
    public function an_unknown_outcome_needs_checking_and_blocks_another_send(): void
    {
        $this->fakeErp([fn () => throw new ConnectionException('cURL error 28: Operation timed out')]);
        $this->mapEverything();
        $recap = $this->holdRecap();
        $this->prepare($recap);

        $this->send($recap)->assertRedirect();

        $journals = ClosingItemJournal::query()->where('status', ClosingItemJournal::STATUS_UNCONFIRMED)->get();
        $this->assertCount(2, $journals);
        $this->assertNotNull($journals[0]->correlation_id);
        $this->assertSame(ClosingRecap::STATUS_ATTENTION, $recap->fresh()?->status);
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::ItemJournalWriteUnconfirmed->value]);

        $this->fakeErp();
        $this->send($recap)->assertSessionHasErrors('recap');
        $this->prepare($recap)->assertSessionHasErrors('recap');
        $this->assertSame([], $this->journalCalls());
    }

    #[Test]
    public function an_unconfirmed_journal_is_resolved_by_the_operator(): void
    {
        $this->fakeErp([fn () => throw new ConnectionException('cURL error 28: Operation timed out')]);
        $this->mapEverything();
        $recap = $this->holdRecap();
        $this->prepare($recap);
        $this->send($recap);
        [$waste, $adjustment] = ClosingItemJournal::query()->where('status', ClosingItemJournal::STATUS_UNCONFIRMED)->orderBy('id')->get();

        $this->actingAs($this->processor())
            ->post(route('closing-item-journals.resolve', $waste), ['outcome' => 'created'])
            ->assertSessionHasErrors('itemJournalNum');

        $this->actingAs($this->processor())
            ->post(route('closing-item-journals.resolve', $waste), ['outcome' => 'created', 'itemJournalNum' => 'IJ202610120001'])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->processor())
            ->post(route('closing-item-journals.resolve', $adjustment), ['outcome' => 'not_created'])
            ->assertSessionHasNoErrors();

        $this->assertSame('IJ202610120001', $waste->fresh()?->item_journal_num);
        $this->assertSame(ClosingItemJournal::STATUS_CREATED, $waste->fresh()?->status);
        $this->assertSame(ClosingItemJournal::STATUS_FAILED, $adjustment->fresh()?->status);
        $this->assertSame(2, AuditLog::query()->where('action', AuditAction::ClosingItemJournalResolved->value)->count());

        // Only the one not created is sent again.
        $this->fakeErp();
        $this->send($recap)->assertSessionHasNoErrors();
        $this->assertCount(1, $this->journalCalls());

        // Only an unconfirmed journal can be resolved.
        $this->actingAs($this->processor())
            ->post(route('closing-item-journals.resolve', $waste), ['outcome' => 'not_created'])
            ->assertSessionHasErrors('outcome');
    }

    // ============================================================== revision

    private function sentRecap(): ClosingRecap
    {
        $this->fakeErp();
        $this->mapEverything();
        $recap = $this->holdRecap(['sequence' => 1]);
        $this->prepare($recap);
        $this->send($recap);

        return $recap;
    }

    #[Test]
    public function a_newer_sequence_after_sending_marks_the_recap_revised(): void
    {
        $recap = $this->sentRecap();

        $this->holdRecap(['sequence' => 2]);

        $this->assertSame(ClosingRecap::STATUS_REVISED, $recap->fresh()?->status);
    }

    #[Test]
    public function a_revision_deletes_the_journals_sent_before_then_creates_the_new_ones(): void
    {
        $recap = $this->sentRecap();
        $this->holdRecap(['sequence' => 2, 'items' => [ClosingTotals::closingItem(['waste' => 3, 'adjustment' => 0])]]);

        $this->fakeErp();
        $this->prepare($recap)->assertSessionHasNoErrors();
        $this->sent = [];
        $this->send($recap)->assertSessionHasNoErrors();

        $calls = array_map(fn (array $c): string => $c['method'].' '.str_replace(self::ESB, '', $c['url']), $this->journalCalls());
        $this->assertSame([
            'DELETE /inventory/item-journal/IJ-NEW-1',
            'DELETE /inventory/item-journal/IJ-NEW-2',
            'POST /inventory/item-journal',
        ], $calls);

        $this->assertSame(2, ClosingItemJournal::query()->where('status', ClosingItemJournal::STATUS_DELETED)->count());
        $current = ClosingItemJournal::query()->where('recap_sequence', 2)->where('status', ClosingItemJournal::STATUS_CREATED)->sole();
        $this->assertSame([['productDetailId' => 801, 'qty' => '-600'], ['productDetailId' => 811, 'qty' => '-4.5']], self::lines($current));
        $this->assertSame(ClosingRecap::STATUS_SENT, $recap->fresh()?->status);
    }

    #[Test]
    public function a_refused_delete_stops_the_revision_before_anything_is_created(): void
    {
        $recap = $this->sentRecap();
        $this->holdRecap(['sequence' => 2]);
        $this->fakeErp();
        $this->prepare($recap);

        $this->fakeErp([fn () => Http::response([
            ...CoreInventory::writeEnvelope('EC03100005', 'Item journal is already authorized', 'fail'),
        ], 400)]);
        $this->send($recap)->assertRedirect();

        $this->assertSame(['DELETE'], array_values(array_unique(array_column($this->journalCalls(), 'method'))));
        $this->assertSame(2, ClosingItemJournal::query()->where('status', ClosingItemJournal::STATUS_DELETE_REFUSED)->count());
        $this->assertSame(2, ClosingItemJournal::query()->where('recap_sequence', 2)->where('status', ClosingItemJournal::STATUS_PREPARED)->count());
        $this->assertSame(ClosingRecap::STATUS_ATTENTION, $recap->fresh()?->status);
    }

    #[Test]
    public function an_unknown_delete_is_checked_with_a_read(): void
    {
        $recap = $this->sentRecap();
        $this->holdRecap(['sequence' => 2]);
        $this->fakeErp();
        $this->prepare($recap);

        // The delete times out, but the journal is gone when read back.
        $this->fakeErp(
            [
                fn (Request $r) => $r->method() === 'DELETE'
                    ? throw new ConnectionException('cURL error 28: Operation timed out')
                    : Http::response(CoreInventory::writeEnvelope(result: ['itemJournalNum' => 'IJ-REV'])),
            ],
            fn () => self::notFound('/inventory/item-journal/IJ-NEW-1'),
        );
        $this->send($recap)->assertSessionHasNoErrors();

        $this->assertSame(2, ClosingItemJournal::query()->where('status', ClosingItemJournal::STATUS_DELETED)->count());
        $this->assertSame(2, ClosingItemJournal::query()->where('recap_sequence', 2)->where('status', ClosingItemJournal::STATUS_CREATED)->count());
    }

    #[Test]
    public function an_unknown_delete_of_a_journal_still_there_needs_checking(): void
    {
        $recap = $this->sentRecap();
        $this->holdRecap(['sequence' => 2]);
        $this->fakeErp();
        $this->prepare($recap);

        $this->fakeErp([fn () => throw new ConnectionException('cURL error 28: Operation timed out')]);
        $this->send($recap)->assertRedirect();

        $this->assertSame(2, ClosingItemJournal::query()->where('status', ClosingItemJournal::STATUS_DELETE_UNCONFIRMED)->count());
        $this->assertSame(0, count(array_filter($this->journalCalls(), fn (array $c): bool => $c['method'] === 'POST')));

        $old = ClosingItemJournal::query()->where('status', ClosingItemJournal::STATUS_DELETE_UNCONFIRMED)->firstOrFail();
        $this->actingAs($this->processor())
            ->post(route('closing-item-journals.resolve', $old), ['outcome' => 'deleted'])
            ->assertSessionHasNoErrors();
        $this->assertSame(ClosingItemJournal::STATUS_DELETED, $old->fresh()?->status);
    }

    // ================================================================ screen

    #[Test]
    public function the_detail_lists_the_journals_and_what_the_operator_may_do(): void
    {
        $this->fakeErp();
        $this->mapEverything();
        $recap = $this->holdRecap();
        $this->prepare($recap);

        $this->actingAs($this->processor())
            ->get(route('closing-recaps.show', $recap))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('recap.status', ClosingRecap::STATUS_PREPARED)
                ->where('canProcess', true)
                ->where('actions.prepare', true)
                ->where('actions.send', true)
                ->has('journals', 2)
                ->where('journals.0.kind', 'waste')
                ->where('journals.0.purposeName', 'Waste')
                ->where('journals.0.status', ClosingItemJournal::STATUS_PREPARED)
                ->where('journals.0.sequence', 2)
                ->where('journals.0.lines.0.productName', 'Beras')
                ->where('journals.0.lines.0.qty', '-200')
                ->where('journals.0.canResolve', false));

        $this->actingAs($this->userWithRole(Role::ItAdmin))
            ->get(route('closing-recaps.show', $recap))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('canProcess', false));
    }
}
