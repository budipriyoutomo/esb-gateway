<?php

namespace Tests\Feature\Disbursements;

use App\Authorization\Role;
use App\Models\AuditLog;
use App\Models\IntegrationLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreDisbursement;
use Tests\TestCase;

/**
 * Authorize / reject / delete of a disbursement ([ESB-038], S2): exactly
 * one attempt, a definite failure reported as one, an ambiguous one as
 * "check before retrying", one write per disbursement at a time, and an
 * audit row with the person -- the ERP records the gateway's credential.
 */
class DisbursementWriteTest extends TestCase
{
    use RefreshDatabase;

    private const UPSTREAM = 'https://stg7.esb.co.id/core-int/finance/disbursement/'.CoreDisbursement::NUMBER;

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
    private function decide(User $user, string $decision, array $extra = [], string $number = CoreDisbursement::NUMBER): TestResponse
    {
        return $this->actingAs($user)->post(route('disbursements.decisions.store', $number), ['decision' => $decision, ...$extra]);
    }

    private function destroy(User $user, string $number = CoreDisbursement::NUMBER): TestResponse
    {
        return $this->actingAs($user)->delete(route('disbursements.destroy', $number));
    }

    private function showRoute(): string
    {
        return route('disbursements.show', CoreDisbursement::NUMBER);
    }

    private function fakeOk(): void
    {
        Http::fake(['*' => Http::response(CoreDisbursement::writeEnvelope())]);
    }

    private function fakeTimeout(int &$attempts): void
    {
        Http::fake(function () use (&$attempts): never {
            $attempts++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });
    }

    #[Test]
    public function changing_a_disbursement_needs_esb_invoke_and_its_own_permission(): void
    {
        Http::fake();

        $this->post(route('disbursements.decisions.store', CoreDisbursement::NUMBER), ['decision' => 'authorize'])
            ->assertRedirect(route('login'));

        $operator = $this->userWithRole(Role::IntegrationOperator, 'simple-purchases.approve', 'simple-purchases.manage');
        $withoutInvoke = $this->userWithRole(Role::Viewer, 'disbursements.approve', 'disbursements.manage');
        $approver = $this->userWithRole(Role::IntegrationOperator, 'disbursements.approve');
        $manager = $this->userWithRole(Role::IntegrationOperator, 'disbursements.manage');

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
    public function an_approver_can_authorize_the_disbursement(): void
    {
        $this->fakeOk();

        $approver = $this->userWithRole(Role::IntegrationOperator, 'disbursements.approve');

        $this->decide($approver, 'authorize', ['reason' => 'ignored'])
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PATCH'
            && $request->url() === self::UPSTREAM.'/authorize'
            && $request->data() === []);

        $audit = AuditLog::query()->sole();
        $this->assertSame('disbursement.authorized', $audit->action);
        $this->assertSame($approver->id, $audit->actor_id);
        $this->assertSame(CoreDisbursement::NUMBER, $audit->metadata['number']);
        $this->assertSame('esb', $audit->metadata['provider']);
        $this->assertArrayNotHasKey('reason', $audit->metadata);
        $this->assertSame('disbursements.authorize', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function an_approver_can_reject_the_disbursement_with_a_reason(): void
    {
        $this->fakeOk();

        $this->decide($this->userWithRole(Role::SuperAdmin), 'reject', ['reason' => 'Wrong supplier'])
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PATCH'
            && $request->url() === self::UPSTREAM.'/reject'
            && $request->data() === ['reason' => 'Wrong supplier']);

        $audit = AuditLog::query()->sole();
        $this->assertSame('disbursement.rejected', $audit->action);
        $this->assertSame('Wrong supplier', $audit->metadata['reason']);
    }

    #[Test]
    public function a_rejection_without_a_reason_sends_no_reason(): void
    {
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

        $this->decide($admin, 'authorize', number: 'import')->assertNotFound();
        $this->destroy($admin, 'import')->assertNotFound();
        $this->actingAs($admin)
            ->post('/disbursements/DB1%2F..%2Fimport/decisions', ['decision' => 'authorize'])
            ->assertNotFound();

        Http::assertNothingSent();
    }

    #[Test]
    public function a_business_rejection_is_reported_and_not_retried(): void
    {
        Http::fake(['*' => Http::response(CoreDisbursement::writeEnvelope('EC03100003', 'Only New documents can be authorized', 'fail'), 400)]);

        $this->decide($this->userWithRole(Role::SuperAdmin), 'authorize')
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'error');

        Http::assertSentCount(1);
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function an_unknown_disbursement_is_a_404(): void
    {
        Http::fake(['*' => Http::response(CoreDisbursement::writeEnvelope('EC03100004', 'Not Found', 'fail'), 400)]);

        $this->decide($this->userWithRole(Role::SuperAdmin), 'authorize')->assertNotFound();

        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function a_decision_timeout_is_not_retried_and_is_audited_as_unconfirmed(): void
    {
        $attempts = 0;
        $this->fakeTimeout($attempts);

        $this->decide($this->userWithRole(Role::SuperAdmin), 'reject', ['reason' => 'Wrong supplier'])
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $attempts);

        $audit = AuditLog::query()->sole();
        $this->assertSame('disbursement.write-unconfirmed', $audit->action);
        $this->assertSame('reject', $audit->metadata['operation']);
        $this->assertSame(CoreDisbursement::NUMBER, $audit->metadata['number']);
        $this->assertNotEmpty($audit->metadata['correlation_id']);
    }

    #[Test]
    public function a_second_write_while_one_is_in_flight_is_refused(): void
    {
        Http::fake();

        $admin = $this->userWithRole(Role::SuperAdmin);
        $lock = Cache::lock('disbursement-write:'.CoreDisbursement::NUMBER, 60);
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
    public function a_manager_can_delete_the_disbursement(): void
    {
        $this->fakeOk();

        $manager = $this->userWithRole(Role::IntegrationOperator, 'disbursements.manage');

        $this->destroy($manager)
            ->assertRedirect(route('disbursements.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE' && $request->url() === self::UPSTREAM);

        $audit = AuditLog::query()->sole();
        $this->assertSame('disbursement.deleted', $audit->action);
        $this->assertSame($manager->id, $audit->actor_id);
        $this->assertSame('disbursements.delete', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function an_unconfirmed_delete_is_audited_and_lands_on_the_list(): void
    {
        $attempts = 0;
        $this->fakeTimeout($attempts);

        $this->destroy($this->userWithRole(Role::SuperAdmin))
            ->assertRedirect(route('disbursements.index'))
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $attempts);
        $this->assertSame('delete', AuditLog::query()->sole()->metadata['operation']);
    }
}
