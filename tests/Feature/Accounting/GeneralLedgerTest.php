<?php

namespace Tests\Feature\Accounting;

use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreAccounting;

/**
 * The general ledger ([ESB-042], S1): POSTs the vendor documents as reads,
 * sent through postRead() (ADR-025) and so retried like a GET. Nothing is
 * asked of the ERP until the account and the period are given; the entries
 * come back unpaged and are paged by the gateway; no balance is computed.
 */
class GeneralLedgerTest extends AccountingTestCase
{
    /**
     * @return array<string, mixed>
     */
    private function period(): array
    {
        return ['coaNo' => '1101', 'dateFrom' => '2026-03-01', 'dateTo' => '2026-03-31'];
    }

    #[Test]
    public function reading_needs_esb_invoke(): void
    {
        Http::fake();

        $this->get(route('general-ledger.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('general-ledger.index', $this->period()))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('general-ledger.summary'))->assertForbidden();

        $this->assertNothingReachedTheEsb();
    }

    #[Test]
    public function the_page_asks_for_the_account_and_period_before_calling_the_erp(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('general-ledger.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('general-ledger/index')
                ->where('entries', null)
                ->where('pagination', null));

        $this->assertNothingReachedTheEsb();
    }

    #[Test]
    public function the_entries_are_read_with_the_filters_under_the_vendors_names(): void
    {
        Http::fake(['*' => Http::response(CoreAccounting::ledger([CoreAccounting::ledgerEntry()]))]);

        $this->actingAs($this->operator())
            ->get(route('general-ledger.index', [
                ...$this->period(),
                'branchId' => 3,
                'side' => 'credit',
                'reference' => 'DB2026',
                'notes' => '',
                'transactionType' => 'Disbursement',
            ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('general-ledger/index')
                ->where('entries.0.coaNo', '1101')
                ->where('entries.0.credit', 250000)
                ->where('pagination.count', 1)
                ->where('filters.side', 'credit'));

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://stg7.esb.co.id/core-int/accounting/general-ledger'
            && $request->data() === [
                'coaNo' => '1101',
                'dateFrom' => '2026-03-01',
                'dateTo' => '2026-03-31',
                'branchID' => 3,
                'debitCreditFilter' => 2,
                'reference' => 'DB2026',
                'transactionType' => 'Disbursement',
            ]);
    }

    #[Test]
    public function the_gateway_pages_the_unpaged_entries(): void
    {
        $entries = array_map(
            static fn (int $i): array => CoreAccounting::ledgerEntry(['referenceNumber' => 'REF'.$i]),
            range(1, 45),
        );
        Http::fake(['*' => Http::response(CoreAccounting::ledger($entries))]);

        $this->actingAs($this->operator())
            ->get(route('general-ledger.index', [...$this->period(), 'page' => 3]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('entries', 5)
                ->where('entries.0.reference', 'REF41')
                ->where('pagination.page', 3)
                ->where('pagination.count', 45)
                ->where('pagination.limit', 20));
    }

    #[Test]
    public function a_failed_read_is_retried_as_a_read(): void
    {
        Http::fakeSequence()
            ->push(['status' => 'fail', 'code' => 'EC03100001', 'message' => 'Internal Server Error', 'result' => null, 'errors' => null], 503)
            ->push(CoreAccounting::ledger([CoreAccounting::ledgerEntry()]));

        $this->actingAs($this->operator())
            ->get(route('general-ledger.index', $this->period()))
            ->assertOk();

        Http::assertSentCount(2);
    }

    #[Test]
    public function an_incomplete_or_too_long_period_never_reaches_the_erp(): void
    {
        Http::fake();

        foreach ([
            ['coaNo' => '1101'],
            ['coaNo' => '1101', 'dateFrom' => '2026-03-01'],
            ['dateFrom' => '2026-03-01', 'dateTo' => '2026-03-31'],
            [...$this->period(), 'dateTo' => '2026-02-01'],
            [...$this->period(), 'dateFrom' => '2025-01-01'],
            [...$this->period(), 'coaNo' => '11 01;'],
            [...$this->period(), 'side' => 'both'],
        ] as $query) {
            $this->actingAs($this->operator())->get(route('general-ledger.index', $query))->assertSessionHasErrors();
        }

        $this->assertNothingReachedTheEsb();
    }

    #[Test]
    public function the_summary_is_read_per_period_with_the_vendors_paging(): void
    {
        Http::fake(['*' => Http::response(CoreAccounting::summary([CoreAccounting::summaryRow()], count: 61, page: 2))]);

        $this->actingAs($this->operator())
            ->get(route('general-ledger.summary', ['dateFrom' => '2026-03-01', 'dateTo' => '2026-03-31', 'accountNo' => '1101', 'branchId' => 3, 'page' => 2]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('general-ledger/summary')
                ->where('rows.0.accountNo', '1101')
                ->where('rows.0.debit', 1000000)
                ->where('pagination.count', 61)
                ->missing('pagination.next'));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://stg7.esb.co.id/core-int/accounting/general-ledger/summary'
            && $request->data() === [
                'startPeriod' => '2026-03-01',
                'endPeriod' => '2026-03-31',
                'accountNo' => '1101',
                'branchID' => 3,
                'page' => 2,
                'limit' => 20,
            ]);
    }

    #[Test]
    public function the_summary_waits_for_a_period(): void
    {
        Http::fake();

        $this->actingAs($this->operator())
            ->get(route('general-ledger.summary'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('general-ledger/summary')
                ->where('rows', null));

        $this->actingAs($this->operator())
            ->get(route('general-ledger.summary', ['dateFrom' => '2026-03-01']))
            ->assertSessionHasErrors('dateTo');

        $this->assertNothingReachedTheEsb();
    }

    #[Test]
    public function the_erps_refusal_is_shown_on_the_page(): void
    {
        Http::fake(['*' => Http::response(CoreAccounting::failure(), 400)]);

        $this->actingAs($this->operator())
            ->get(route('general-ledger.index', $this->period()))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('entries', null)
                ->where('error', fn (string $message): bool => str_contains($message, 'rejected this request')));
    }
}
