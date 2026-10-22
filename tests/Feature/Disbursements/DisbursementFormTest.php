<?php

namespace Tests\Feature\Disbursements;

use App\Authorization\Role;
use App\Models\AuditLog;
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
use Tests\Fixtures\CoreDisbursement;
use Tests\TestCase;

/**
 * Create / update of a disbursement ([ESB-039], S3): esb.invoke +
 * disbursements.manage, the vendor schema checked before anything is sent,
 * the ERP's own pre-check (PUT .../validate) before an edit, exactly one
 * attempt, the ERP's field errors back on the form (ADR-021), an unconfirmed
 * write audited with its correlation id. An update sends back what the form
 * does not show -- the bank account number from the ERP, never the browser.
 */
class DisbursementFormTest extends TestCase
{
    use RefreshDatabase;

    private const UPSTREAM = 'https://stg7.esb.co.id/core-int/finance/disbursement';

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

    private function manager(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::IntegrationOperator->value);
        $user->givePermissionTo('disbursements.manage');

        return $user;
    }

    /**
     * A credit disbursement.
     *
     * @return array<string, mixed>
     */
    private function creditInput(): array
    {
        return [
            'date' => '2026-03-25',
            'branchId' => 3,
            'paymentId' => 2,
            'currencyId' => 1,
            'rate' => 1,
            'supplierId' => 14,
            'creditTerm' => 30,
            'reference' => 'INV-RUKO-03',
            'invoiceDueDate' => '2026-04-24',
            'notes' => 'Sewa ruko Maret',
            'lines' => [
                ['branchId' => 3, 'coaNo' => '6101', 'description' => 'Sewa Maret', 'amount' => 15000000.5],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cashInput(): array
    {
        return [
            'date' => '2026-03-25',
            'branchId' => 3,
            'paymentId' => 1,
            'currencyId' => 1,
            'rate' => 1,
            'cashBankCoaNo' => '1101',
            'notes' => '',
            'lines' => [
                ['branchId' => 3, 'coaNo' => '6102', 'description' => 'Listrik', 'amount' => 250000],
                ['branchId' => 4, 'coaNo' => '6103', 'description' => 'Air', 'amount' => 75000.25],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function creditBody(): array
    {
        return [
            'disbursementDate' => '2026-03-25',
            'branchID' => 3,
            'paymentID' => 2,
            'currencyID' => 1,
            'rate' => 1,
            'supplierID' => 14,
            'creditTerm' => 30,
            'ref' => 'INV-RUKO-03',
            'invoiceDueDate' => '2026-04-24',
            'additionalInformation' => 'Sewa ruko Maret',
            'disbursementDetails' => [
                ['branchID' => 3, 'coaNo' => '6101', 'description' => 'Sewa Maret', 'amount' => 15000000.5],
            ],
        ];
    }

    /**
     * Answer the detail on GET and $write on anything else.
     *
     * @param  array<string, mixed>|null  $write
     */
    private function fakeUpstream(?array $write = null, int $writeStatus = 200): void
    {
        Http::fake(fn (Request $request) => $request->method() === 'GET'
            ? Http::response(CoreDisbursement::detail())
            : Http::response($write ?? CoreDisbursement::writeEnvelope(), $writeStatus));
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function store(array $input, ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->manager())->post(route('disbursements.store'), $input);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function update(array $input, ?User $user = null, string $number = CoreDisbursement::NUMBER): TestResponse
    {
        return $this->actingAs($user ?? $this->manager())->put(route('disbursements.update', $number), $input);
    }

    private function showRoute(): string
    {
        return route('disbursements.show', CoreDisbursement::NUMBER);
    }

    /**
     * @return list<string> "METHOD URL" of every request but the reads.
     */
    private function writesSent(): array
    {
        return array_values(array_map(
            static fn (array $pair): string => $pair[0]->method().' '.$pair[0]->url(),
            Http::recorded(fn (Request $request): bool => $request->method() !== 'GET')->all(),
        ));
    }

    // ---------------------------------------------------------------- access

    #[Test]
    public function the_forms_need_esb_invoke_and_the_manage_permission(): void
    {
        Http::fake();

        $approver = User::factory()->create();
        $approver->assignRole(Role::IntegrationOperator->value);
        $approver->givePermissionTo('disbursements.approve');
        $withoutInvoke = User::factory()->create();
        $withoutInvoke->assignRole(Role::Viewer->value);
        $withoutInvoke->givePermissionTo('disbursements.manage');
        $operator = User::factory()->create();
        $operator->assignRole(Role::IntegrationOperator->value);

        foreach ([$operator, $approver, $withoutInvoke] as $user) {
            $this->actingAs($user)->get(route('disbursements.create'))->assertForbidden();
            $this->actingAs($user)->get(route('disbursements.edit', CoreDisbursement::NUMBER))->assertForbidden();
            $this->store($this->creditInput(), $user)->assertForbidden();
            $this->update($this->creditInput(), $user)->assertForbidden();
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function the_create_form_needs_no_erp_call(): void
    {
        Http::fake();

        $this->actingAs($this->manager())
            ->get(route('disbursements.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('disbursements/form')
                ->where('disbursement', null));

        Http::assertNothingSent();
    }

    // ---------------------------------------------------------------- create

    #[Test]
    public function a_manager_can_create_a_credit_disbursement(): void
    {
        $this->fakeUpstream(CoreDisbursement::writeEnvelope(result: ['disbursementNum' => CoreDisbursement::NUMBER]));

        $manager = $this->manager();

        $this->store($this->creditInput(), $manager)
            ->assertSessionHasNoErrors()
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === self::UPSTREAM
            && $request->data() === $this->creditBody());

        $audit = AuditLog::query()->sole();
        $this->assertSame('disbursement.created', $audit->action);
        $this->assertSame($manager->id, $audit->actor_id);
        $this->assertSame(CoreDisbursement::NUMBER, $audit->metadata['number']);
        $this->assertSame('2026-03-25', $audit->metadata['date']);
        $this->assertSame(2, $audit->metadata['payment']);
        $this->assertSame(1, $audit->metadata['lines']);
    }

    #[Test]
    public function a_cash_disbursement_sends_its_cash_account_and_no_credit_fields(): void
    {
        // The vendor's example answers {}: no number, so the list.
        $this->fakeUpstream(CoreDisbursement::writeEnvelope(result: []));

        $this->store($this->cashInput())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('disbursements.index'));

        Http::assertSent(fn (Request $request): bool => $request->data() === [
            'disbursementDate' => '2026-03-25',
            'branchID' => 3,
            'paymentID' => 1,
            'currencyID' => 1,
            'rate' => 1,
            'disbursementCoaNo' => '1101',
            'additionalInformation' => '',
            'disbursementDetails' => [
                ['branchID' => 3, 'coaNo' => '6102', 'description' => 'Listrik', 'amount' => 250000],
                ['branchID' => 4, 'coaNo' => '6103', 'description' => 'Air', 'amount' => 75000.25],
            ],
        ]);
        $this->assertArrayNotHasKey('number', AuditLog::query()->sole()->metadata);
    }

    #[Test]
    public function the_vendors_rules_are_checked_before_the_call(): void
    {
        Http::fake();

        $credit = $this->creditInput();
        $cash = $this->cashInput();

        $this->store([...$credit, 'lines' => []])->assertSessionHasErrors('lines');
        $this->store([...$credit, 'date' => '25-03-2026'])->assertSessionHasErrors('date');
        $this->store([...$credit, 'paymentId' => 3])->assertSessionHasErrors('paymentId');
        $this->store([...$credit, 'rate' => 0])->assertSessionHasErrors('rate');
        // Credit: a supplier, no cash / bank account.
        $this->store([...$credit, 'supplierId' => null])->assertSessionHasErrors('supplierId');
        $this->store([...$credit, 'cashBankCoaNo' => '1101'])->assertSessionHasErrors('cashBankCoaNo');
        $this->store([...$credit, 'creditTerm' => 100000])->assertSessionHasErrors('creditTerm');
        $this->store([...$credit, 'reference' => str_repeat('x', 51)])->assertSessionHasErrors('reference');
        // Cash: a cash / bank account, no credit term or reference.
        $this->store([...$cash, 'cashBankCoaNo' => null])->assertSessionHasErrors('cashBankCoaNo');
        $this->store([...$cash, 'creditTerm' => 30])->assertSessionHasErrors('creditTerm');
        $this->store([...$cash, 'reference' => 'X'])->assertSessionHasErrors('reference');
        // Lines.
        $this->store([...$cash, 'lines' => [['branchId' => 3, 'coaNo' => '6102', 'description' => str_repeat('x', 101), 'amount' => 1]]])
            ->assertSessionHasErrors('lines.0.description');
        $this->store([...$cash, 'lines' => [['branchId' => 3, 'coaNo' => '6102', 'description' => 'x', 'amount' => 1.23456]]])
            ->assertSessionHasErrors('lines.0.amount');
        $this->store([...$cash, 'lines' => [['branchId' => null, 'coaNo' => '', 'description' => 'x', 'amount' => 1]]])
            ->assertSessionHasErrors(['lines.0.branchId', 'lines.0.coaNo']);
        $this->store([...$cash, 'notes' => str_repeat('x', 1001)])->assertSessionHasErrors('notes');

        Http::assertNothingSent();
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function the_erps_field_errors_land_on_the_form(): void
    {
        $this->fakeUpstream([
            ...CoreDisbursement::writeEnvelope('EC03100003', 'Validation Error', 'fail'),
            'errors' => [['attribute' => 'disbursementDate', 'code' => 'EC03100003', 'message' => 'Must be after the locked period']],
        ], 400);

        $this->store($this->creditInput())
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

        $this->store($this->creditInput())
            ->assertRedirect(route('disbursements.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $attempts);

        $audit = AuditLog::query()->sole();
        $this->assertSame('disbursement.write-unconfirmed', $audit->action);
        $this->assertSame('create', $audit->metadata['operation']);
        $this->assertNotEmpty($audit->metadata['correlation_id']);
    }

    #[Test]
    public function one_create_per_person_at_a_time(): void
    {
        Http::fake();

        $manager = $this->manager();
        $lock = Cache::lock('disbursement-create:'.$manager->id, 60);
        $lock->get();

        try {
            $this->store($this->creditInput(), $manager)->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        Http::assertNothingSent();
    }

    // ---------------------------------------------------------------- update

    #[Test]
    public function the_edit_form_asks_the_erp_whether_the_disbursement_can_be_edited(): void
    {
        $this->fakeUpstream();

        $this->actingAs($this->manager())
            ->get(route('disbursements.edit', CoreDisbursement::NUMBER))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('disbursements/form')
                ->where('disbursement.number', CoreDisbursement::NUMBER)
                ->missing('disbursement.carryOver'));

        $this->assertSame(['PUT '.self::UPSTREAM.'/'.CoreDisbursement::NUMBER.'/validate'], $this->writesSent());
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/validate') && $request->data() === []);
    }

    #[Test]
    public function a_disbursement_the_erp_will_not_let_change_is_not_edited_here(): void
    {
        $this->fakeUpstream(CoreDisbursement::writeEnvelope('EC03100003', 'The period has been closed', 'fail'), 400);

        $this->actingAs($this->manager())
            ->get(route('disbursements.edit', CoreDisbursement::NUMBER))
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->update($this->creditInput())
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        // Only the two pre-checks went out; no update, nothing audited.
        $this->assertSame(array_fill(0, 2, 'PUT '.self::UPSTREAM.'/'.CoreDisbursement::NUMBER.'/validate'), $this->writesSent());
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function an_unanswered_pre_check_sends_no_update(): void
    {
        $writes = [];
        Http::fake(function (Request $request) use (&$writes) {
            if ($request->method() === 'GET') {
                return Http::response(CoreDisbursement::detail());
            }

            $writes[] = $request->url();

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->update($this->creditInput())
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        // The pre-check saves nothing: neither retried nor audited as unconfirmed.
        $this->assertSame([self::UPSTREAM.'/'.CoreDisbursement::NUMBER.'/validate'], $writes);
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function editing_an_unknown_or_unusable_number_is_a_404(): void
    {
        Http::fake(['*' => Http::response(CoreDisbursement::notFoundResponse('/finance/disbursement/'.CoreDisbursement::NUMBER), 400)]);

        $this->actingAs($this->manager())->get(route('disbursements.edit', CoreDisbursement::NUMBER))->assertNotFound();
        $this->actingAs($this->manager())->get(route('disbursements.edit', 'import'))->assertNotFound();
        $this->update($this->creditInput(), number: 'import')->assertNotFound();

        Http::assertSentCount(1);
    }

    #[Test]
    public function a_manager_can_update_and_what_the_form_does_not_show_is_kept(): void
    {
        $this->fakeUpstream();

        $manager = $this->manager();

        $this->update($this->creditInput(), $manager)
            ->assertSessionHasNoErrors()
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        $this->assertSame([
            'PUT '.self::UPSTREAM.'/'.CoreDisbursement::NUMBER.'/validate',
            'PUT '.self::UPSTREAM.'/'.CoreDisbursement::NUMBER,
        ], $this->writesSent());
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && $request->url() === self::UPSTREAM.'/'.CoreDisbursement::NUMBER
            && $request->data() === [
                ...$this->creditBody(),
                'costCenterID' => 7,
                'projectID' => 4,
                'bankName' => 'BCA',
                'bankAccountName' => 'PT Properti Jaya',
                'bankAccountNumber' => '1234567890123',
            ]);

        $audit = AuditLog::query()->sole();
        $this->assertSame('disbursement.updated', $audit->action);
        $this->assertSame($manager->id, $audit->actor_id);
        $this->assertSame(CoreDisbursement::NUMBER, $audit->metadata['number']);
    }

    #[Test]
    public function an_update_is_sent_once_even_when_unconfirmed(): void
    {
        $updates = 0;
        Http::fake(function (Request $request) use (&$updates) {
            if ($request->method() === 'GET') {
                return Http::response(CoreDisbursement::detail());
            }

            if (str_ends_with($request->url(), '/validate')) {
                return Http::response(CoreDisbursement::writeEnvelope());
            }

            $updates++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $this->update($this->creditInput())
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $updates);

        $audit = AuditLog::query()->sole();
        $this->assertSame('disbursement.write-unconfirmed', $audit->action);
        $this->assertSame('update', $audit->metadata['operation']);
    }

    #[Test]
    public function an_update_shares_the_disbursements_write_lock(): void
    {
        $this->fakeUpstream();

        $lock = Cache::lock('disbursement-write:'.CoreDisbursement::NUMBER, 60);
        $lock->get();

        try {
            $this->update($this->creditInput())->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        $this->assertNotContains('PUT '.self::UPSTREAM.'/'.CoreDisbursement::NUMBER, $this->writesSent());
    }
}
