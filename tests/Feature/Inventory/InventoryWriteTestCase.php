<?php

namespace Tests\Feature\Inventory;

use App\Authorization\Role;
use App\Domain\Canonical\InventoryKind;
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
use Tests\Fixtures\CoreInventory;
use Tests\TestCase;

/**
 * Authorize / reject / delete of one inventory document (TODO-modules.md §3,
 * S2). The vendor documents no idempotency key for any of them: exactly one
 * attempt, a definite failure reported as one, an ambiguous one as "check
 * before retrying", one write per document at a time, and an audit row with
 * the person -- the ERP records the gateway's credential.
 *
 * Each kind supplies its paths and names.
 */
abstract class InventoryWriteTestCase extends TestCase
{
    use RefreshDatabase;

    abstract protected function kind(): InventoryKind;

    /** e.g. "/inventory/goods-delivery". */
    abstract protected function upstreamPath(): string;

    abstract protected function number(): string;

    /** The audit action prefix, e.g. "goods-delivery". */
    abstract protected function auditPrefix(): string;

    /** Whether the vendor's reject takes a reason (only the item journal's). */
    protected function rejectTakesReason(): bool
    {
        return false;
    }

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

    protected function userWithRole(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    /**
     * Only Super Admin holds the new inventory permissions until the business
     * confirms which roles do (docs/06).
     */
    protected function superAdmin(): User
    {
        return $this->userWithRole(Role::SuperAdmin);
    }

    /**
     * An operator (esb.invoke) holding every inventory permission of the
     * other kinds.
     */
    protected function operatorWithOtherKindsPermissions(): User
    {
        $user = $this->userWithRole(Role::IntegrationOperator);

        foreach (InventoryKind::cases() as $kind) {
            if ($kind !== $this->kind()) {
                $user->givePermissionTo($kind->value.'.approve', $kind->value.'.manage');
            }
        }

        return $user;
    }

    protected function upstreamUrl(string $suffix = ''): string
    {
        return 'https://stg7.esb.co.id/core-int'.$this->upstreamPath().'/'.$this->number().$suffix;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    protected function decide(User $user, string $decision, array $extra = [], ?string $number = null): TestResponse
    {
        return $this->actingAs($user)->post(
            route($this->kind()->value.'.decisions.store', $number ?? $this->number()),
            ['decision' => $decision, ...$extra],
        );
    }

    protected function destroy(User $user, ?string $number = null): TestResponse
    {
        return $this->actingAs($user)->delete(route($this->kind()->value.'.destroy', $number ?? $this->number()));
    }

    protected function showRoute(): string
    {
        return route($this->kind()->value.'.show', $this->number());
    }

    protected function indexRoute(): string
    {
        return route($this->kind()->value.'.index');
    }

    protected function fakeOk(): void
    {
        Http::fake(['*' => Http::response(CoreInventory::writeEnvelope())]);
    }

    protected function fakeTimeout(int &$attempts): void
    {
        Http::fake(function () use (&$attempts): never {
            $attempts++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });
    }

    // ---------------------------------------------------------------- access

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        Http::fake();

        $this->post(route($this->kind()->value.'.decisions.store', $this->number()), ['decision' => 'authorize'])
            ->assertRedirect(route('login'));
        $this->delete(route($this->kind()->value.'.destroy', $this->number()))
            ->assertRedirect(route('login'));

        Http::assertNothingSent();
    }

    #[Test]
    public function reading_erp_data_is_not_enough_to_change_a_document(): void
    {
        Http::fake();

        $operator = $this->userWithRole(Role::IntegrationOperator);

        $this->decide($operator, 'authorize')->assertForbidden();
        $this->destroy($operator)->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function another_documents_permissions_do_not_grant_this_one(): void
    {
        Http::fake();

        $user = $this->operatorWithOtherKindsPermissions();

        $this->decide($user, 'authorize')->assertForbidden();
        $this->destroy($user)->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function the_documents_permissions_are_not_enough_without_esb_invoke(): void
    {
        Http::fake();

        $user = $this->userWithRole(Role::Viewer);
        $user->givePermissionTo($this->kind()->value.'.approve', $this->kind()->value.'.manage');

        $this->decide($user, 'authorize')->assertForbidden();
        $this->destroy($user)->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function approving_does_not_grant_deleting_and_managing_does_not_grant_deciding(): void
    {
        Http::fake();

        $approver = $this->userWithRole(Role::IntegrationOperator);
        $approver->givePermissionTo($this->kind()->value.'.approve');
        $manager = $this->userWithRole(Role::IntegrationOperator);
        $manager->givePermissionTo($this->kind()->value.'.manage');

        $this->destroy($approver)->assertForbidden();
        $this->decide($manager, 'authorize')->assertForbidden();

        Http::assertNothingSent();
    }

    // ------------------------------------------------------------- decisions

    #[Test]
    public function an_approver_can_authorize_the_document(): void
    {
        $this->fakeOk();

        $approver = $this->userWithRole(Role::IntegrationOperator);
        $approver->givePermissionTo($this->kind()->value.'.approve');

        $this->decide($approver, 'authorize')
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PATCH'
            && $request->url() === $this->upstreamUrl('/authorize')
            && $request->data() === []);

        $audit = AuditLog::query()->sole();
        $this->assertSame($this->auditPrefix().'.authorized', $audit->action);
        $this->assertSame($approver->id, $audit->actor_id);
        $this->assertSame($this->number(), $audit->metadata['number']);
        $this->assertSame('esb', $audit->metadata['provider']);
        $this->assertSame($this->kind()->value.'.authorize', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function a_reason_sent_with_an_authorize_is_not_forwarded(): void
    {
        $this->fakeOk();

        $this->decide($this->superAdmin(), 'authorize', ['reason' => 'ignored'])->assertRedirect($this->showRoute());

        Http::assertSent(fn (Request $request): bool => $request->data() === []);
        $this->assertArrayNotHasKey('reason', AuditLog::query()->sole()->metadata);
    }

    #[Test]
    public function an_approver_can_reject_the_document(): void
    {
        $this->fakeOk();

        $this->decide($this->superAdmin(), 'reject', ['reason' => 'Wrong destination'])
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        $expected = $this->rejectTakesReason() ? ['reason' => 'Wrong destination'] : [];

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PATCH'
            && $request->url() === $this->upstreamUrl('/reject')
            && $request->data() === $expected);

        $audit = AuditLog::query()->sole();
        $this->assertSame($this->auditPrefix().'.rejected', $audit->action);

        // A reason the vendor does not take is neither sent nor recorded as
        // if it had been.
        if ($this->rejectTakesReason()) {
            $this->assertSame('Wrong destination', $audit->metadata['reason']);
        } else {
            $this->assertArrayNotHasKey('reason', $audit->metadata);
        }
    }

    #[Test]
    public function an_unknown_decision_is_refused_before_the_call(): void
    {
        Http::fake();

        $this->decide($this->superAdmin(), 'finish')->assertSessionHasErrors('decision');

        Http::assertNothingSent();
    }

    #[Test]
    public function a_number_without_a_digit_or_escaping_the_path_never_reaches_the_esb(): void
    {
        Http::fake();

        $admin = $this->superAdmin();

        // Literal vendor paths share the number's segment.
        $this->decide($admin, 'authorize', number: 'initialize')->assertNotFound();
        $this->destroy($admin, 'create')->assertNotFound();
        $this->actingAs($admin)
            ->post('/'.$this->kind()->value.'/X1%2F..%2Fdelete/decisions', ['decision' => 'authorize'])
            ->assertNotFound();

        Http::assertNothingSent();
    }

    #[Test]
    public function a_business_rejection_is_reported_and_not_retried(): void
    {
        Http::fake(['*' => Http::response(CoreInventory::writeEnvelope('EC03100003', 'Validation Error', 'fail'), 400)]);

        $this->decide($this->superAdmin(), 'authorize')
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'error');

        Http::assertSentCount(1);
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function an_unknown_document_is_a_404(): void
    {
        Http::fake(['*' => Http::response(CoreInventory::writeEnvelope('EC03100004', 'Not Found', 'fail'), 400)]);

        $this->decide($this->superAdmin(), 'authorize')->assertNotFound();

        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function a_decision_timeout_is_not_retried_and_is_audited_as_unconfirmed(): void
    {
        $attempts = 0;
        $this->fakeTimeout($attempts);

        $this->decide($this->superAdmin(), 'reject', ['reason' => 'Wrong destination'])
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $attempts);

        $audit = AuditLog::query()->sole();
        $this->assertSame($this->auditPrefix().'.write-unconfirmed', $audit->action);
        $this->assertSame('reject', $audit->metadata['operation']);
        $this->assertSame($this->number(), $audit->metadata['number']);
        $this->assertNotEmpty($audit->metadata['correlation_id']);
    }

    #[Test]
    public function a_second_write_while_one_is_in_flight_is_refused(): void
    {
        Http::fake();

        $lock = Cache::lock($this->auditPrefix().'-write:'.$this->number(), 60);
        $lock->get();

        try {
            $this->decide($this->superAdmin(), 'authorize')
                ->assertRedirect($this->showRoute())
                ->assertSessionHas('inertia.flash_data.toast.type', 'warning');
            $this->destroy($this->superAdmin())
                ->assertRedirect($this->showRoute())
                ->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        Http::assertNothingSent();
    }

    // ---------------------------------------------------------------- delete

    #[Test]
    public function a_manager_can_delete_the_document(): void
    {
        $this->fakeOk();

        $manager = $this->userWithRole(Role::IntegrationOperator);
        $manager->givePermissionTo($this->kind()->value.'.manage');

        $this->destroy($manager)
            ->assertRedirect($this->indexRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && $request->url() === $this->upstreamUrl());

        $audit = AuditLog::query()->sole();
        $this->assertSame($this->auditPrefix().'.deleted', $audit->action);
        $this->assertSame($manager->id, $audit->actor_id);
        $this->assertSame($this->number(), $audit->metadata['number']);
        $this->assertSame($this->kind()->value.'.delete', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function a_refused_delete_returns_to_the_document(): void
    {
        Http::fake(['*' => Http::response(CoreInventory::writeEnvelope('EC03100003', 'Validation Error', 'fail'), 400)]);

        $this->destroy($this->superAdmin())
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'error');

        Http::assertSentCount(1);
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function an_unconfirmed_delete_is_audited_and_lands_on_the_list(): void
    {
        $attempts = 0;
        $this->fakeTimeout($attempts);

        $this->destroy($this->superAdmin())
            ->assertRedirect($this->indexRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');

        $this->assertSame(1, $attempts);

        $audit = AuditLog::query()->sole();
        $this->assertSame($this->auditPrefix().'.write-unconfirmed', $audit->action);
        $this->assertSame('delete', $audit->metadata['operation']);
    }
}
