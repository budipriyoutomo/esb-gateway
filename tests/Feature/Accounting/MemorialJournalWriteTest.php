<?php

namespace Tests\Feature\Accounting;

use App\Authorization\Role;
use App\Models\AuditLog;
use App\Models\IntegrationLog;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreAccounting;

/**
 * Authorize / reject / delete of a memorial journal ([ESB-044], S2):
 * exactly one attempt, a definite failure reported as one, an ambiguous one
 * as "check before retrying", one write per journal at a time, and an audit
 * row with the person -- the ERP records the gateway's credential.
 */
class MemorialJournalWriteTest extends AccountingTestCase
{
    private const UPSTREAM = 'https://stg7.esb.co.id/core-int/accounting/memorial-journal/'.CoreAccounting::MJ;

    private function userWithRole(Role $role, string ...$permissions): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        if ($permissions !== []) {
            $user->givePermissionTo(...$permissions);
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function decide(User $user, string $decision, array $extra = [], string $number = CoreAccounting::MJ): TestResponse
    {
        return $this->actingAs($user)->post(route('memorial-journals.decisions.store', $number), ['decision' => $decision, ...$extra]);
    }

    private function destroy(User $user, string $number = CoreAccounting::MJ): TestResponse
    {
        return $this->actingAs($user)->delete(route('memorial-journals.destroy', $number));
    }

    private function showRoute(): string
    {
        return route('memorial-journals.show', CoreAccounting::MJ);
    }

    private function fakeOk(): void
    {
        Http::fake(['*' => Http::response(CoreAccounting::mjWrite())]);
    }

    private function fakeTimeout(int &$attempts): void
    {
        Http::fake(function () use (&$attempts): never {
            $attempts++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });
    }

    #[Test]
    public function changing_a_journal_needs_esb_invoke_and_its_own_permission(): void
    {
        Http::fake();

        $this->post(route('memorial-journals.decisions.store', CoreAccounting::MJ), ['decision' => 'authorize'])
            ->assertRedirect(route('login'));

        $operator = $this->userWithRole(Role::IntegrationOperator, 'disbursements.approve', 'disbursements.manage');
        $withoutInvoke = $this->userWithRole(Role::Viewer, 'memorial-journals.approve', 'memorial-journals.manage');
        $approver = $this->userWithRole(Role::IntegrationOperator, 'memorial-journals.approve');
        $manager = $this->userWithRole(Role::IntegrationOperator, 'memorial-journals.manage');

        foreach ([$operator, $withoutInvoke] as $user) {
            $this->decide($user, 'authorize')->assertForbidden();
            $this->destroy($user)->assertForbidden();
        }

        // Approving does not grant deleting, and managing does not grant deciding.
        $this->destroy($approver)->assertForbidden();
        $this->decide($manager, 'authorize')->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function an_approver_can_authorize_the_journal(): void
    {
        $this->fakeOk();

        $approver = $this->userWithRole(Role::IntegrationOperator, 'memorial-journals.approve');

        $this->decide($approver, 'authorize', ['reason' => 'ignored'])
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PATCH'
            && $request->url() === self::UPSTREAM.'/authorize'
            && $request->data() === []);

        $audit = AuditLog::query()->sole();
        $this->assertSame('memorial-journal.authorized', $audit->action);
        $this->assertSame($approver->id, $audit->actor_id);
        $this->assertSame(CoreAccounting::MJ, $audit->metadata['number']);
        $this->assertSame('esb', $audit->metadata['provider']);
        $this->assertArrayNotHasKey('reason', $audit->metadata);
        $this->assertSame('memorial-journals.authorize', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function an_approver_can_reject_the_journal_with_a_reason(): void
    {
        $this->fakeOk();

        $this->decide($this->userWithRole(Role::SuperAdmin), 'reject', ['reason' => 'Wrong account'])
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PATCH'
            && $request->url() === self::UPSTREAM.'/reject'
            && $request->data() === ['reason' => 'Wrong account']);

        $audit = AuditLog::query()->sole();
        $this->assertSame('memorial-journal.rejected', $audit->action);
        $this->assertSame('Wrong account', $audit->metadata['reason']);
    }

    #[Test]
    public function a_rejection_without_a_reason_is_left_to_the_erp(): void
    {
        // Required only while waiting for approval: the ERP knows the status.
        $this->fakeOk();

        $this->decide($this->userWithRole(Role::SuperAdmin), 'reject', ['reason' => '  '])->assertRedirect($this->showRoute());

        Http::assertSent(fn (Request $request): bool => $request->data() === []);
        $this->assertArrayNotHasKey('reason', AuditLog::query()->sole()->metadata);
    }

    #[Test]
    public function a_bad_decision_or_reason_is_refused_before_the_call(): void
    {
        Http::fake();

        $admin = $this->userWithRole(Role::SuperAdmin);

        $this->decide($admin, 'finish')->assertSessionHasErrors('decision');
        $this->decide($admin, 'reject', ['reason' => str_repeat('x', 501)])->assertSessionHasErrors('reason');

        Http::assertNothingSent();
    }

    #[Test]
    public function a_number_without_a_digit_or_escaping_the_path_never_reaches_the_esb(): void
    {
        Http::fake();

        $admin = $this->userWithRole(Role::SuperAdmin);

        $this->decide($admin, 'authorize', number: 'upload')->assertNotFound();
        $this->destroy($admin, 'export')->assertNotFound();
        $this->actingAs($admin)
            ->post('/memorial-journals/MJ1%2F..%2Fupload/decisions', ['decision' => 'authorize'])
            ->assertNotFound();

        Http::assertNothingSent();
    }

    #[Test]
    public function a_business_rejection_is_reported_and_not_retried(): void
    {
        Http::fake(['*' => Http::response(CoreAccounting::mjWrite('EC03100003', 'A reason is required', 'fail'), 400)]);

        $this->decide($this->userWithRole(Role::SuperAdmin), 'reject')
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'error');

        Http::assertSentCount(1);
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function an_unknown_journal_is_a_404(): void
    {
        Http::fake(['*' => Http::response(CoreAccounting::mjWrite('EC03100004', 'Not Found', 'fail'), 400)]);

        $this->decide($this->userWithRole(Role::SuperAdmin), 'authorize')->assertNotFound();

        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function a_decision_timeout_is_not_retried_and_is_audited_as_unconfirmed(): void
    {
        $attempts = 0;
        $this->fakeTimeout($attempts);

        $this->decide($this->userWithRole(Role::SuperAdmin), 'reject', ['reason' => 'Wrong account'])
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.unconfirmedWrite.reference');

        $this->assertSame(1, $attempts);

        $audit = AuditLog::query()->sole();
        $this->assertSame('memorial-journal.write-unconfirmed', $audit->action);
        $this->assertSame('reject', $audit->metadata['operation']);
        $this->assertSame(CoreAccounting::MJ, $audit->metadata['number']);
        $this->assertNotEmpty($audit->metadata['correlation_id']);
    }

    #[Test]
    public function a_second_write_while_one_is_in_flight_is_refused(): void
    {
        Http::fake();

        $admin = $this->userWithRole(Role::SuperAdmin);
        $lock = Cache::lock('memorial-journal-write:'.CoreAccounting::MJ, 60);
        $lock->get();

        try {
            $this->decide($admin, 'authorize')->assertSessionHas('inertia.flash_data.toast.type', 'warning');
            $this->destroy($admin)->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function a_manager_can_delete_the_journal(): void
    {
        $this->fakeOk();

        $manager = $this->userWithRole(Role::IntegrationOperator, 'memorial-journals.manage');

        $this->destroy($manager)
            ->assertRedirect(route('memorial-journals.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE' && $request->url() === self::UPSTREAM);

        $audit = AuditLog::query()->sole();
        $this->assertSame('memorial-journal.deleted', $audit->action);
        $this->assertSame($manager->id, $audit->actor_id);
        $this->assertSame('memorial-journals.delete', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function an_unconfirmed_delete_is_audited_and_lands_on_the_list(): void
    {
        $attempts = 0;
        $this->fakeTimeout($attempts);

        $this->destroy($this->userWithRole(Role::SuperAdmin))
            ->assertRedirect(route('memorial-journals.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $attempts);
        $this->assertSame('delete', AuditLog::query()->sole()->metadata['operation']);
    }
}
