<?php

namespace Tests\Feature\Accounting;

use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreAccounting;

/**
 * The general journal ([ESB-041], S1): esb.invoke, the scalar filters under
 * the vendor's names (array filters are not sent), paging without the ESB's
 * own URLs, the lines of one journal by its positive head id.
 */
class GeneralJournalTest extends AccountingTestCase
{
    private function fakeUpstream(): void
    {
        Http::fake(fn (Request $request) => str_contains($request->url(), '/detail')
            ? Http::response(CoreAccounting::journalLines([CoreAccounting::journalLine(), CoreAccounting::journalLine(['journalDetailID' => 990002, 'coaNo' => '1101', 'drAmount' => 0, 'crAmount' => 15000000.5])], count: 42, page: 2))
            : Http::response(CoreAccounting::journalList([CoreAccounting::journalRow()], count: 45, page: 2)));
    }

    #[Test]
    public function reading_needs_esb_invoke(): void
    {
        Http::fake();

        $this->get(route('general-journals.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('general-journals.index'))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('general-journals.show', CoreAccounting::JOURNAL_HEAD_ID))->assertForbidden();

        $this->assertNothingReachedTheEsb();
    }

    #[Test]
    public function the_list_shows_the_entries_and_sends_the_filters_under_the_vendors_names(): void
    {
        $this->fakeUpstream();

        $this->actingAs($this->operator())
            ->get(route('general-journals.index', [
                'dateFrom' => '2026-03-01',
                'dateTo' => '2026-03-31',
                'search' => 'sewa',
                'page' => 2,
            ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('general-journals/index')
                ->where('entries.0.journalHeadId', CoreAccounting::JOURNAL_HEAD_ID)
                ->where('pagination.count', 45)
                ->where('filters.search', 'sewa')
                ->missing('pagination.next'));

        Http::assertSent(function (Request $request): bool {
            $this->assertSame('GET', $request->method());
            $this->assertStringStartsWith('https://stg7.esb.co.id/core-int/accounting/general-journal/search?', $request->url());
            $this->assertSame([
                'dateFrom' => '2026-03-01',
                'dateTo' => '2026-03-31',
                'journalSearch' => 'sewa',
                'limit' => '20',
                'page' => '2',
            ], self::sentQuery($request));

            return true;
        });
    }

    #[Test]
    public function bad_filters_never_reach_the_esb(): void
    {
        Http::fake();

        foreach ([
            ['limit' => 101],
            ['dateFrom' => '01/03/2026'],
            ['dateFrom' => '2026-03-31', 'dateTo' => '2026-03-01'],
            ['search' => str_repeat('x', 256)],
        ] as $query) {
            $this->actingAs($this->operator())->get(route('general-journals.index', $query))->assertSessionHasErrors();
        }

        $this->assertNothingReachedTheEsb();
    }

    #[Test]
    public function the_journal_shows_its_lines_page_by_page(): void
    {
        $this->fakeUpstream();

        $this->actingAs($this->operator())
            ->get(route('general-journals.show', ['journalHeadId' => CoreAccounting::JOURNAL_HEAD_ID, 'page' => 2]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('general-journals/show')
                ->where('journalHeadId', CoreAccounting::JOURNAL_HEAD_ID)
                ->has('lines', 2)
                ->where('lines.0.debit', 15000000.5)
                ->where('pagination.count', 42)
                ->missing('pagination.next'));

        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://stg7.esb.co.id/core-int/accounting/general-journal/'.CoreAccounting::JOURNAL_HEAD_ID.'/detail?')
            && self::sentQuery($request) === ['limit' => '50', 'page' => '2']);
    }

    #[Test]
    public function an_unknown_or_unusable_journal_is_a_404(): void
    {
        Http::fake(['*' => Http::response(CoreAccounting::notFoundResponse('/accounting/general-journal/1/detail'), 400)]);

        $this->actingAs($this->operator())->get(route('general-journals.show', 1))->assertNotFound();

        foreach (['export', 'search', '0', '-1', rawurlencode('1/2')] as $id) {
            $this->actingAs($this->operator())->get('/general-journals/'.$id)->assertNotFound();
        }

        Http::assertSentCount(1);
    }
}
