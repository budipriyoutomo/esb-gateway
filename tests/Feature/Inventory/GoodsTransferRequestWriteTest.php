<?php

namespace Tests\Feature\Inventory;

use App\Authorization\Role;
use App\Domain\Canonical\InventoryKind;
use App\Models\AuditLog;
use App\Models\IntegrationLog;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreInventory;

/**
 * Goods transfer requests (TODO-modules.md §3, S2): authorize / reject /
 * delete, and finish / reopen -- closing an authorized or delivering request
 * so nothing more is delivered against it, and taking that back.
 */
class GoodsTransferRequestWriteTest extends InventoryWriteTestCase
{
    protected function kind(): InventoryKind
    {
        return InventoryKind::GoodsTransferRequests;
    }

    protected function upstreamPath(): string
    {
        return '/inventory/goods-transfer-request';
    }

    protected function number(): string
    {
        return CoreInventory::GTR;
    }

    protected function auditPrefix(): string
    {
        return 'goods-transfer-request';
    }

    /**
     * @param  'finish'|'reopen'  $operation
     */
    private function complete(User $user, string $operation, ?string $number = null): TestResponse
    {
        return $this->actingAs($user)->patch(route('goods-transfer-requests.'.$operation, $number ?? $this->number()));
    }

    #[Test]
    public function finishing_and_reopening_need_esb_invoke_and_the_manage_permission(): void
    {
        Http::fake();

        $approver = $this->userWithRole(Role::IntegrationOperator);
        $approver->givePermissionTo('goods-transfer-requests.approve');
        $withoutInvoke = $this->userWithRole(Role::Viewer);
        $withoutInvoke->givePermissionTo('goods-transfer-requests.manage');

        foreach (['finish', 'reopen'] as $operation) {
            $this->complete($this->userWithRole(Role::IntegrationOperator), $operation)->assertForbidden();
            $this->complete($approver, $operation)->assertForbidden();
            $this->complete($withoutInvoke, $operation)->assertForbidden();
            $this->complete($this->operatorWithOtherKindsPermissions(), $operation)->assertForbidden();
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function a_manager_can_finish_a_transfer_request(): void
    {
        $this->fakeOk();

        $manager = $this->userWithRole(Role::IntegrationOperator);
        $manager->givePermissionTo('goods-transfer-requests.manage');

        $this->complete($manager, 'finish')
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PATCH'
            && $request->url() === $this->upstreamUrl('/finish')
            && $request->data() === []);

        $audit = AuditLog::query()->sole();
        $this->assertSame('goods-transfer-request.finished', $audit->action);
        $this->assertSame($manager->id, $audit->actor_id);
        $this->assertSame($this->number(), $audit->metadata['number']);
        $this->assertSame('goods-transfer-requests.finish', IntegrationLog::query()->sole()->operation);
    }

    #[Test]
    public function a_manager_can_reopen_a_finished_transfer_request(): void
    {
        $this->fakeOk();

        $this->complete($this->superAdmin(), 'reopen')
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'success');

        // The vendor calls it "unfinished".
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PATCH'
            && $request->url() === $this->upstreamUrl('/unfinished'));

        $this->assertSame('goods-transfer-request.reopened', AuditLog::query()->sole()->action);
    }

    #[Test]
    public function a_refused_finish_is_reported_and_not_retried(): void
    {
        Http::fake(['*' => Http::response(CoreInventory::writeEnvelope('EC03100003', 'Validation Error', 'fail'), 400)]);

        $this->complete($this->superAdmin(), 'finish')
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'error');

        Http::assertSentCount(1);
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function an_unconfirmed_reopen_is_audited(): void
    {
        $attempts = 0;
        $this->fakeTimeout($attempts);

        $this->complete($this->superAdmin(), 'reopen')
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.unconfirmedWrite.reference');

        $this->assertSame(1, $attempts);

        $audit = AuditLog::query()->sole();
        $this->assertSame('goods-transfer-request.write-unconfirmed', $audit->action);
        $this->assertSame('reopen', $audit->metadata['operation']);
    }

    #[Test]
    public function finishing_shares_the_documents_write_lock(): void
    {
        Http::fake();

        $lock = Cache::lock('goods-transfer-request-write:'.$this->number(), 60);
        $lock->get();

        try {
            $this->complete($this->superAdmin(), 'finish')
                ->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        } finally {
            $lock->release();
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function finishing_an_unusable_number_never_reaches_the_esb(): void
    {
        Http::fake();

        $this->complete($this->superAdmin(), 'finish', 'detail')->assertNotFound();

        Http::assertNothingSent();
    }
}
