<?php

namespace Tests\Feature\Inventory;

use App\Domain\Canonical\InventoryKind;
use App\Models\AuditLog;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CoreInventory;

/**
 * Goods transfer requests (TODO-modules.md §3, S3): products from one branch
 * to another. Only inventory requests (categoryTypeID 1); asset requests
 * stay in the ERP. The update is a PATCH of the whole request and keeps the
 * purchase requests it was raised from.
 */
class GoodsTransferRequestFormTest extends InventoryFormTestCase
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

    protected function updateMethod(): string
    {
        return 'PATCH';
    }

    protected function validInput(): array
    {
        return [
            'date' => '2026-03-25',
            'branchId' => 1,
            'locationId' => 100,
            'destinationBranchId' => 3,
            'notes' => 'Restock outlet',
            'lines' => [
                ['productDetailId' => 501, 'qty' => 20, 'requestQty' => 25],
                ['productDetailId' => 502, 'qty' => 2.5],
            ],
        ];
    }

    /**
     * @param  list<string>|null  $purchaseRequests
     * @return array<string, mixed>
     */
    private function body(?array $purchaseRequests): array
    {
        return [
            'transferDate' => '2026-03-25',
            'originBranchID' => 1,
            'originLocationID' => 100,
            'destinationBranchID' => 3,
            'categoryTypeID' => 1,
            'additionalInfo' => 'Restock outlet',
            'purchaseRequestNum' => $purchaseRequests,
            'transferDetailAssets' => [],
            'transferDetails' => [
                ['productDetailID' => 501, 'qty' => 20, 'requestQty' => 25],
                ['productDetailID' => 502, 'qty' => 2.5],
            ],
        ];
    }

    protected function expectedCreateBody(): array
    {
        // The vendor: "Send null when not used."
        return $this->body(null);
    }

    protected function expectedUpdateBody(): array
    {
        return $this->body(['PR202603190001']);
    }

    protected function detailResponse(): array
    {
        return CoreInventory::gtrDetail(['canEdit' => true, 'canDelete' => true, 'statusID' => 1, 'statusName' => 'New']);
    }

    protected function assetDetailResponse(): ?array
    {
        return CoreInventory::gtrDetail(['canEdit' => true, 'transferDetailAssets' => ['AST-1']]);
    }

    protected function createdResult(): ?array
    {
        return ['transferNum' => CoreInventory::GTR];
    }

    protected function vendorDateField(): string
    {
        return 'transferDate';
    }

    #[Test]
    public function a_request_the_erp_says_cannot_be_edited_is_not_edited_here(): void
    {
        $this->fakeUpstream(CoreInventory::gtrDetail(['canEdit' => false]));

        $this->actingAs($this->manager())
            ->get(route('goods-transfer-requests.edit', $this->number()))
            ->assertRedirect($this->showRoute())
            ->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        $this->update($this->validInput())->assertRedirect($this->showRoute());

        Http::assertNotSent(fn (Request $request): bool => $request->method() !== 'GET');
        $this->assertSame(0, AuditLog::query()->count());
    }

    #[Test]
    public function the_origin_location_is_optional(): void
    {
        $this->fakeUpstream(write: CoreInventory::writeEnvelope(result: []));

        $this->store([...$this->validInput(), 'locationId' => null])->assertSessionHasNoErrors();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && ! array_key_exists('originLocationID', $request->data()));
    }

    #[Test]
    public function the_vendors_line_rules_are_checked_before_the_call(): void
    {
        Http::fake();

        // Each product detail once; qty greater than 0; requested qty at least 0.
        $this->store([...$this->validInput(), 'lines' => [['productDetailId' => 501, 'qty' => 1], ['productDetailId' => 501, 'qty' => 2]]])
            ->assertSessionHasErrors('lines.1.productDetailId');
        $this->store([...$this->validInput(), 'lines' => [['productDetailId' => 501, 'qty' => 0]]])
            ->assertSessionHasErrors('lines.0.qty');
        $this->store([...$this->validInput(), 'lines' => [['productDetailId' => 501, 'qty' => 1, 'requestQty' => -1]]])
            ->assertSessionHasErrors('lines.0.requestQty');
        $this->store([...$this->validInput(), 'destinationBranchId' => null])
            ->assertSessionHasErrors('destinationBranchId');

        Http::assertNothingSent();
    }
}
