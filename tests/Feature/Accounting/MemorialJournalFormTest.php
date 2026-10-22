<?php

namespace Tests\Feature\Accounting;

use App\Authorization\Role;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreAccounting;

/**
 * Create / update of a memorial journal ([ESB-045], S3): esb.invoke +
 * memorial-journals.manage, the vendor schema checked before anything is
 * sent, exactly one attempt, the ERP's field errors back on the form
 * (ADR-021), an unconfirmed write audited with its correlation id. Balance
 * is the ERP's to judge. An update sends back each kept line's cost center
 * and project from the ERP, never from the browser.
 */
class MemorialJournalFormTest extends AccountingTestCase
{
    private const UPSTREAM = 'https://stg7.esb.co.id/core-int/accounting/memorial-journal';

    private function manager(): User
    {
        $user = $this->operator();
        $user->givePermissionTo('memorial-journals.manage');

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function input(): array
    {
        return [
            'date' => '2026-03-26',
            'notes' => 'Koreksi biaya sewa',
            'lines' => [
                ['branchId' => 3, 'coaNo' => '6101', 'currencyId' => 1, 'rate' => 1, 'debit' => 500000.25, 'credit' => null, 'notes' => 'Koreksi'],
                ['branchId' => 3, 'coaNo' => '2101', 'currencyId' => 1, 'rate' => 1, 'debit' => null, 'credit' => 500000.25, 'notes' => ''],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function body(): array
    {
        return [
            'memorialJournalDate' => '2026-03-26',
            'additionalInfo' => 'Koreksi biaya sewa',
            'memorialJournalDetails' => [
                ['branchID' => 3, 'coaNo' => '6101', 'currencyID' => 1, 'rate' => 1, 'drAmount' => 500000.25, 'notes' => 'Koreksi'],
                ['branchID' => 3, 'coaNo' => '2101', 'currencyID' => 1, 'rate' => 1, 'crAmount' => 500000.25],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    private static function line(array $line): array
    {
        return ['branchId' => 3, 'coaNo' => '6101', 'currencyId' => 1, 'rate' => 1, 'debit' => 1, 'credit' => null, ...$line];
    }

    /**
     * Answer the detail on GET and $write on anything else.
     *
     * @param  array<string, mixed>|null  $write
     * @param  array<string, mixed>  $detail
     */
    private function fakeUpstream(?array $write = null, int $writeStatus = 200, array $detail = []): void
    {
        Http::fake(fn (Request $request) => $request->method() === 'GET'
            ? Http::response(CoreAccounting::mjDetail($detail))
            : Http::response($write ?? CoreAccounting::mjWrite(), $writeStatus));
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function store(array $input, ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->manager())->post(route('memorial-journals.store'), $input);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function update(array $input, ?User $user = null, string $number = CoreAccounting::MJ): TestResponse
    {
        return $this->actingAs($user ?? $this->manager())->put(route('memorial-journals.update', $number), $input);
    }

    private function showRoute(): string
    {
        return route('memorial-journals.show', CoreAccounting::MJ);
    }

    // ---------------------------------------------------------------- access

    #[Test]
    public function the_forms_need_esb_invoke_and_the_manage_permission(): void
    {
        Http::fake();

        $approver = $this->operator();
        $approver->givePermissionTo('memorial-journals.approve');
        $withoutInvoke = User::factory()->create();
        $withoutInvoke->assignRole(Role::Viewer->value);
        $withoutInvoke->givePermissionTo('memorial-journals.manage');

        foreach ([$this->operator(), $approver, $withoutInvoke] as $user) {
            $this->actingAs($user)->get(route('memorial-journals.create'))->assertForbidden();
            $this->actingAs($user)->get(route('memorial-journals.edit', CoreAccounting::MJ))->assertForbidden();
            $this->store($this->input(), $user)->assertForbidden();
            $this->update($this->input(), $user)->assertForbidden();
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function the_create_form_needs_no_erp_call(): void
    {
        Http::fake();

        $this->actingAs($this->manager())
            ->get(route('memorial-journals.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('memorial-journals/form')
                ->where('journal', null));

        Http::assertNothingSent();
    }

    // ---------------------------------------------------------------- create

    #[Test]
    public function a_manager_can_create_a_journal(): void
    {
        $this->fakeUpstream(CoreAccounting::mjWrite(result: ['memorialJournalNum' => CoreAccounting::MJ]));

        $manager = $this->manager();

        $this->store($this->input(), $manager)
            ->assertSessionHasNoErrors()
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === self::UPSTREAM
            && $request->data() === $this->body());

        $audit = AuditLog::query()->sole();
        $this->assertSame('memorial-journal.created', $audit->action);
        $this->assertSame($manager->id, $audit->actor_id);
        $this->assertSame(CoreAccounting::MJ, $audit->metadata['number']);
        $this->assertSame('2026-03-26', $audit->metadata['date']);
        $this->assertSame(2, $audit->metadata['lines']);
    }

    #[Test]
    public function without_a_number_from_the_erp_the_create_lands_on_the_list(): void
    {
        // The vendor's example answers {}.
        $this->fakeUpstream(CoreAccounting::mjWrite(result: []));

        $this->store([...$this->input(), 'notes' => null])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('memorial-journals.index'));

        // The notes are always sent: "" clears them on an edit.
        Http::assertSent(fn (Request $request): bool => $request->data()['additionalInfo'] === '');
        $this->assertArrayNotHasKey('number', AuditLog::query()->sole()->metadata);
    }

    #[Test]
    public function an_unbalanced_journal_is_the_erps_to_refuse(): void
    {
        $this->fakeUpstream();

        $input = $this->input();
        $input['lines'][1]['credit'] = 1;

        $this->store($input)->assertSessionHasNoErrors();

        Http::assertSentCount(1);
    }

    #[Test]
    public function the_vendors_rules_are_checked_before_the_call(): void
    {
        Http::fake();

        $input = $this->input();
        [$debit] = $input['lines'];

        $this->store([...$input, 'lines' => [$debit]])->assertSessionHasErrors('lines');
        $this->store([...$input, 'date' => '26-03-2026'])->assertSessionHasErrors('date');
        $this->store([...$input, 'notes' => str_repeat('x', 1001)])->assertSessionHasErrors('notes');
        // Exactly one of debit or credit, never negative, four decimals at most.
        $this->store([...$input, 'lines' => [$debit, self::line(['debit' => null, 'credit' => null])]])->assertSessionHasErrors('lines.1.debit');
        $this->store([...$input, 'lines' => [$debit, self::line(['debit' => 1, 'credit' => 1])]])->assertSessionHasErrors('lines.1.credit');
        $this->store([...$input, 'lines' => [$debit, self::line(['debit' => -1])]])->assertSessionHasErrors('lines.1.debit');
        $this->store([...$input, 'lines' => [$debit, self::line(['debit' => 1.23456])]])->assertSessionHasErrors('lines.1.debit');
        // Branch, account, currency, rate, notes.
        $this->store([...$input, 'lines' => [$debit, self::line(['branchId' => null, 'coaNo' => '', 'currencyId' => null])]])
            ->assertSessionHasErrors(['lines.1.branchId', 'lines.1.coaNo', 'lines.1.currencyId']);
        $this->store([...$input, 'lines' => [$debit, self::line(['rate' => 0])]])->assertSessionHasErrors('lines.1.rate');
        $this->store([...$input, 'lines' => [$debit, self::line(['notes' => str_repeat('x', 101)])]])->assertSessionHasErrors('lines.1.notes');

        Http::assertNothingSent();
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function the_erps_field_errors_land_on_the_form(): void
    {
        $this->fakeUpstream([
            ...CoreAccounting::mjWrite('EC03100003', 'Validation Error', 'fail'),
            'errors' => [['attribute' => 'memorialJournalDate', 'code' => 'EC03100003', 'message' => 'Must be after the locked period']],
        ], 400);

        $this->store($this->input())
            ->assertSessionHasErrors(['date' => 'Must be after the locked period']);

        Http::assertSentCount(1);
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function an_unconfirmed_create_is_audited_once_and_lands_on_the_list(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts): never {
            $attempts++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->store($this->input())
            ->assertRedirect(route('memorial-journals.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $attempts);

        $audit = AuditLog::query()->sole();
        $this->assertSame('memorial-journal.write-unconfirmed', $audit->action);
        $this->assertSame('create', $audit->metadata['operation']);
        $this->assertNotEmpty($audit->metadata['correlation_id']);
    }

    #[Test]
    public function one_create_per_person_at_a_time(): void
    {
        Http::fake();

        $manager = $this->manager();
        $lock = Cache::lock('memorial-journal-create:'.$manager->id, 60);
        $lock->get();

        try {
            $this->store($this->input(), $manager)->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        Http::assertNothingSent();
    }

    // ---------------------------------------------------------------- update

    #[Test]
    public function the_edit_form_shows_the_journal_without_what_stays_on_the_server(): void
    {
        $this->fakeUpstream();

        $this->actingAs($this->manager())
            ->get(route('memorial-journals.edit', CoreAccounting::MJ))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('memorial-journals/form')
                ->where('journal.number', CoreAccounting::MJ)
                ->where('journal.lines.0.id', 71)
                ->where('journal.lines.0.currencyId', 1)
                ->missing('journal.carryOver'));

        Http::assertSentCount(1);
    }

    #[Test]
    public function editing_an_unknown_or_unusable_number_is_a_404(): void
    {
        Http::fake(['*' => Http::response(CoreAccounting::notFoundResponse('/accounting/memorial-journal/'.CoreAccounting::MJ), 400)]);

        $this->actingAs($this->manager())->get(route('memorial-journals.edit', CoreAccounting::MJ))->assertNotFound();
        $this->actingAs($this->manager())->get(route('memorial-journals.edit', 'upload'))->assertNotFound();
        $this->update($this->input(), number: 'export')->assertNotFound();
        $this->update($this->input())->assertNotFound();

        Http::assertSentCount(2);
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function a_manager_can_update_and_each_kept_lines_cost_center_and_project_are_sent_back(): void
    {
        $detail = CoreAccounting::mjDetailResult();
        $detail['memorialJournalDetails'][0] = [...$detail['memorialJournalDetails'][0], 'costCenterID' => 7, 'projectID' => 4];
        $this->fakeUpstream(detail: ['memorialJournalDetails' => $detail['memorialJournalDetails']]);

        $input = $this->input();
        // The first line is the ERP's line 71; the browser's cost center is ignored.
        $input['lines'][0] = [...$input['lines'][0], 'id' => 71, 'costCenterId' => 99];
        // An id the journal does not have carries nothing.
        $input['lines'][1] = [...$input['lines'][1], 'id' => 555];

        $manager = $this->manager();

        $this->update($input, $manager)
            ->assertSessionHasNoErrors()
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        $body = $this->body();
        $body['memorialJournalDetails'][0] = [...$body['memorialJournalDetails'][0], 'costCenterID' => 7, 'projectID' => 4];

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && $request->url() === self::UPSTREAM.'/'.CoreAccounting::MJ
            && $request->data() === $body);

        $audit = AuditLog::query()->sole();
        $this->assertSame('memorial-journal.updated', $audit->action);
        $this->assertSame($manager->id, $audit->actor_id);
        $this->assertSame(CoreAccounting::MJ, $audit->metadata['number']);
        $this->assertSame(2, $audit->metadata['lines']);
    }

    #[Test]
    public function an_update_is_sent_once_even_when_unconfirmed(): void
    {
        $updates = 0;
        Http::fake(function (Request $request) use (&$updates) {
            if ($request->method() === 'GET') {
                return Http::response(CoreAccounting::mjDetail());
            }

            $updates++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->update($this->input())
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $updates);

        $audit = AuditLog::query()->sole();
        $this->assertSame('memorial-journal.write-unconfirmed', $audit->action);
        $this->assertSame('update', $audit->metadata['operation']);
    }

    #[Test]
    public function an_update_shares_the_journals_write_lock(): void
    {
        $this->fakeUpstream();

        $lock = Cache::lock('memorial-journal-write:'.CoreAccounting::MJ, 60);
        $lock->get();

        try {
            $this->update($this->input())->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        Http::assertNotSent(fn (Request $request): bool => $request->method() === 'PUT');
    }
}
