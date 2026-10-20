<?php

namespace Tests\Feature\Inventory;

use App\Domain\Canonical\InventoryKind;
use App\Models\AuditLog;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreInventory;

/**
 * Item journals (TODO-modules.md §3, S2): authorize / reject / delete. The
 * only inventory reject that takes a reason; the vendor does not mark it
 * required.
 */
class ItemJournalWriteTest extends InventoryWriteTestCase
{
    protected function kind(): InventoryKind
    {
        return InventoryKind::ItemJournals;
    }

    protected function upstreamPath(): string
    {
        return '/inventory/item-journal';
    }

    protected function number(): string
    {
        return CoreInventory::IJ;
    }

    protected function auditPrefix(): string
    {
        return 'item-journal';
    }

    protected function rejectTakesReason(): bool
    {
        return true;
    }

    #[Test]
    public function a_rejection_without_a_reason_sends_no_reason(): void
    {
        $this->fakeOk();

        $this->decide($this->superAdmin(), 'reject', ['reason' => '   '])->assertRedirect($this->showRoute());

        Http::assertSent(fn (Request $request): bool => $request->data() === []);
        $this->assertArrayNotHasKey('reason', AuditLog::query()->sole()->metadata);
    }

    #[Test]
    public function an_overlong_reason_is_refused_before_the_call(): void
    {
        Http::fake();

        $this->decide($this->superAdmin(), 'reject', ['reason' => str_repeat('x', 501)])
            ->assertSessionHasErrors('reason');

        Http::assertNothingSent();
    }
}
