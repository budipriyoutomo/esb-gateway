<?php

namespace Tests\Feature\SupplierCategories;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\MasterData\MasterDataWriteTestCase;

/**
 * Deleting and restoring supplier categories ([ESB-023], S2). A delete is
 * preceded by GET .../check-delete.
 */
class SupplierCategoryWriteTest extends MasterDataWriteTestCase
{
    protected function resource(): string
    {
        return 'supplier-categories';
    }

    protected function upstreamPath(): string
    {
        return '/supplier/category/5';
    }

    protected function sampleId(): int
    {
        return 5;
    }

    protected function prefix(): string
    {
        return 'supplier-category';
    }

    protected function operations(): array
    {
        return [
            'delete' => ['DELETE', 'DELETE', ''],
            'restore' => ['PATCH', 'PATCH', '/restore'],
        ];
    }

    /**
     * The pre-check answers "can be deleted"; everything else is the write.
     */
    protected function fakeUpstream(Closure $write): void
    {
        Http::fake(fn (Request $request) => str_ends_with($request->url(), '/check-delete')
            ? Http::response($this->envelope(true))
            : $write($request));
    }

    #[Test]
    public function a_category_still_in_use_is_not_deleted(): void
    {
        Http::fake(['*/check-delete' => Http::response($this->envelope(false)), '*' => Http::response($this->envelope())]);

        $this->send('delete')
            ->assertRedirect(route('supplier-categories.show', 5))
            ->assertSessionHas('inertia.flash_data.toast.message', 'Sample is still in use, so the ERP will not delete it.');

        Http::assertSentCount(1);
        Http::assertNotSent(fn (Request $request) => $request->method() === 'DELETE');
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function the_pre_check_is_a_read_and_its_refusal_reaches_the_person(): void
    {
        Http::fake(['*/check-delete' => Http::response([
            'status' => 'fail',
            'code' => 'EC03100003',
            'message' => 'Validation Error',
            'result' => null,
            'errors' => [['field' => 'supplierCategoryID', 'code' => 'EC03100003', 'message' => 'Category is used by 3 suppliers']],
        ], 400)]);

        $this->send('delete')->assertSessionHas('inertia.flash_data.toast.type', 'error');

        $this->assertStringContainsString('Category is used by 3 suppliers', (string) session('inertia.flash_data.toast.message'));
        Http::assertNotSent(fn (Request $request) => $request->method() === 'DELETE');
    }

    #[Test]
    public function supplier_categories_cannot_be_deactivated_here(): void
    {
        $this->assertFalse(app('router')->has('supplier-categories.deactivate'));
    }
}
