<?php

namespace Tests\Feature\Accounting;

use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreAccounting;

/**
 * Memorial journals ([ESB-043], S1): esb.invoke, filters under the vendor's
 * names with empty ones dropped, paging without the ESB's own URLs, 404 for
 * EC03100004 and for numbers that would reach literal vendor paths.
 */
class MemorialJournalTest extends AccountingTestCase
{
    private function fakeUpstream(): void
    {
        Http::fake(fn (Request $request) => str_ends_with((string) parse_url($request->url(), PHP_URL_PATH), '/'.CoreAccounting::MJ)
            ? Http::response(CoreAccounting::mjDetail())
            : Http::response(CoreAccounting::mjList([CoreAccounting::mjRow()], count: 45, page: 2)));
    }

    #[Test]
    public function reading_needs_esb_invoke(): void
    {
        Http::fake();

        $this->get(route('memorial-journals.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('memorial-journals.index'))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('memorial-journals.show', CoreAccounting::MJ))->assertForbidden();

        $this->assertNothingReachedTheEsb();
    }

    #[Test]
    public function the_list_shows_the_rows_and_sends_the_filters_under_the_vendors_names(): void
    {
        $this->fakeUpstream();

        $this->actingAs($this->operator())
            ->get(route('memorial-journals.index', [
                'number' => 'MJ2026',
                'dateFrom' => '2026-03-01',
                'dateTo' => '2026-03-31',
                'statusId' => 38,
                'page' => 2,
            ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('memorial-journals/index')
                ->where('journals.0.number', CoreAccounting::MJ)
                ->where('statuses.1', ['id' => 38, 'name' => 'Waiting For Approval'])
                ->where('pagination.count', 45)
                ->missing('pagination.prev'));

        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://stg7.esb.co.id/core-int/accounting/memorial-journal?')
            && self::sentQuery($request) === [
                'dateFrom' => '2026-03-01',
                'dateTo' => '2026-03-31',
                'limit' => '20',
                'memorialJournalNum' => 'MJ2026',
                'page' => '2',
                'statusID' => '38',
            ]);
    }

    #[Test]
    public function bad_filters_never_reach_the_esb(): void
    {
        Http::fake();

        foreach ([['limit' => 101], ['dateFrom' => 'yesterday'], ['statusId' => 'new']] as $query) {
            $this->actingAs($this->operator())->get(route('memorial-journals.index', $query))->assertSessionHasErrors();
        }

        $this->assertNothingReachedTheEsb();
    }

    #[Test]
    public function the_detail_shows_the_journal_and_its_lines(): void
    {
        $this->fakeUpstream();

        $this->actingAs($this->operator())
            ->get(route('memorial-journals.show', CoreAccounting::MJ))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('memorial-journals/show')
                ->where('journal.number', CoreAccounting::MJ)
                ->has('journal.lines', 2)
                ->where('journal.attachmentCount', 1));
    }

    #[Test]
    public function an_unknown_journal_is_a_404(): void
    {
        Http::fake(['*' => Http::response(CoreAccounting::notFoundResponse('/accounting/memorial-journal/'.CoreAccounting::MJ), 400)]);

        $this->actingAs($this->operator())->get(route('memorial-journals.show', CoreAccounting::MJ))->assertNotFound();
    }

    #[Test]
    public function a_number_that_would_reach_a_literal_vendor_path_never_reaches_the_esb(): void
    {
        Http::fake();

        foreach (['access', 'export', 'form-upload', 'upload', rawurlencode('1/2'), '..'] as $number) {
            $this->actingAs($this->operator())->get('/memorial-journals/'.$number)->assertNotFound();
        }

        $this->assertNothingReachedTheEsb();
    }
}
