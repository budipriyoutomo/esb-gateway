<?php

namespace Tests\Feature\Disbursements;

use App\Authorization\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreDisbursement;
use Tests\TestCase;

/**
 * Disbursement list and detail ([ESB-037], S1): esb.invoke, filters under
 * the vendor's names with empty ones dropped, paging without the ESB's own
 * URLs, 404 for EC03100004 and for numbers that would reach literal vendor
 * paths, and the bank account number never reaching the browser whole.
 */
class DisbursementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seed(RolePermissionSeeder::class);

        config()->set('esb.environment', 'staging_int');
        config()->set('esb.auth.mechanism', 'static_token');
        config()->set('esb.auth.static_token', 'a-static-api-key');
        config()->set('esb.retry.base_delay_ms', 0);
    }

    private function operator(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::IntegrationOperator->value);

        return $user;
    }

    /**
     * @param  list<string>  $sent  Filled with "PATH?QUERY" of every request.
     */
    private function fakeUpstream(array &$sent = []): void
    {
        Http::fake(function (Request $request) use (&$sent) {
            $path = (string) parse_url($request->url(), PHP_URL_PATH);
            $sent[] = $path.'?'.(string) parse_url($request->url(), PHP_URL_QUERY);

            return str_ends_with($path, '/'.CoreDisbursement::NUMBER)
                ? Http::response(CoreDisbursement::detail())
                : Http::response(CoreDisbursement::listResponse([CoreDisbursement::row()], count: 45, page: 2));
        });
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function index(array $query = [], ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->operator())->get(route('disbursements.index', $query));
    }

    private function show(string $number, ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->operator())->get(route('disbursements.show', $number));
    }

    #[Test]
    public function reading_needs_esb_invoke(): void
    {
        Http::fake();

        $this->get(route('disbursements.index'))->assertRedirect(route('login'));
        $this->index(user: User::factory()->create())->assertForbidden();
        $this->show(CoreDisbursement::NUMBER, User::factory()->create())->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function the_list_shows_the_rows_and_the_page_without_the_esbs_urls(): void
    {
        $this->fakeUpstream();

        $this->index(['page' => 2])
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('disbursements/index')
                ->where('disbursements.0.number', CoreDisbursement::NUMBER)
                ->where('disbursements.0.grandTotal', 15000000.5)
                ->where('statuses', [
                    ['id' => 1, 'name' => 'New'],
                    ['id' => 38, 'name' => 'Waiting For Approval'],
                    ['id' => 3, 'name' => 'Authorized'],
                    ['id' => 2, 'name' => 'Rejected'],
                ])
                ->where('pagination.page', 2)
                ->where('pagination.count', 45)
                ->missing('pagination.next')
                ->missing('pagination.prev'));
    }

    #[Test]
    public function filters_reach_the_esb_under_its_names_and_empty_ones_are_dropped(): void
    {
        $sent = [];
        $this->fakeUpstream($sent);

        $this->index([
            'number' => 'DB1',
            'dateFrom' => '2026-03-01',
            'dateTo' => '2026-03-31',
            'statusId' => 38,
            'branchId' => 3,
            'supplierId' => 14,
            'paymentId' => 2,
            'reference' => 'INV',
            'overdue' => 1,
            'search' => '',
        ])->assertOk();

        parse_str((string) parse_url($sent[0], PHP_URL_QUERY), $query);
        $expected = [
            'disbursementNum' => 'DB1',
            'disbursementDateFrom' => '2026-03-01',
            'disbursementDateTo' => '2026-03-31',
            'statusID' => '38',
            'branchID' => '3',
            'supplierID' => '14',
            'paymentID' => '2',
            'ref' => 'INV',
            'isOverDue' => 'true',
            'page' => '1',
            'limit' => '20',
        ];
        ksort($expected);
        ksort($query);

        $this->assertSame($expected, $query);
        $this->assertStringStartsWith('/core-int/finance/disbursement?', $sent[0]);
    }

    #[Test]
    public function not_asking_for_overdue_only_sends_no_overdue_filter(): void
    {
        $sent = [];
        $this->fakeUpstream($sent);

        $this->index(['overdue' => 0])->assertOk();

        $this->assertStringNotContainsString('isOverDue', $sent[0]);
    }

    #[Test]
    public function bad_filters_never_reach_the_esb(): void
    {
        Http::fake();

        foreach ([
            ['limit' => 101],
            ['dateFrom' => '01/03/2026'],
            ['dateFrom' => '2026-03-31', 'dateTo' => '2026-03-01'],
            ['statusId' => 'x'],
            ['paymentId' => 3],
            ['supplierId' => 0],
        ] as $query) {
            $this->index($query)->assertSessionHasErrors();
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function the_detail_shows_the_disbursement_with_its_bank_account(): void
    {
        $this->fakeUpstream();

        $this->show(CoreDisbursement::NUMBER)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('disbursements/show')
                ->where('disbursement.number', CoreDisbursement::NUMBER)
                ->where('disbursement.bankAccountNumber', '1234567890123')
                ->has('disbursement.lines', 1)
                ->has('disbursement.linkedDocuments', 1)
                ->missing('disbursement.carryOver'));
    }

    #[Test]
    public function an_unknown_disbursement_is_a_404(): void
    {
        Http::fake(['*' => Http::response(CoreDisbursement::notFoundResponse('/finance/disbursement/'.CoreDisbursement::NUMBER), 400)]);

        $this->show(CoreDisbursement::NUMBER)->assertNotFound();
    }

    #[Test]
    public function a_number_that_would_reach_a_literal_vendor_path_never_reaches_the_esb(): void
    {
        Http::fake();

        foreach (['import', 'validate', rawurlencode('1/2'), '..'] as $number) {
            $this->actingAs($this->operator())->get('/disbursements/'.$number)->assertNotFound();
        }

        Http::assertNothingSent();
    }
}
