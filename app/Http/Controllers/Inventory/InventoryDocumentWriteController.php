<?php

namespace App\Http\Controllers\Inventory;

use App\Actions\Inventory\CompleteGoodsTransferRequest;
use App\Actions\Inventory\DecideInventoryDocument;
use App\Actions\Inventory\DeleteInventoryDocument;
use App\Domain\Canonical\InventoryKind;
use App\Http\Concerns\ResolvesInventoryKind;
use App\Http\Controllers\Concerns\HandlesDocumentWrites;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\DecideInventoryDocumentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Authorize / reject / delete an inventory document, and finish / reopen a
 * goods transfer request ([ESB-034]). The kind is a route default; the
 * permissions are checked by the route (delete, finish, reopen) or the form
 * request (decisions). Every outcome lands where the person can see the
 * document's state as the ERP now has it.
 */
class InventoryDocumentWriteController extends Controller
{
    use HandlesDocumentWrites;
    use ResolvesInventoryKind;

    public function decide(string $number, DecideInventoryDocumentRequest $request, DecideInventoryDocument $decide): RedirectResponse
    {
        $this->assertUsableNumber($number);

        $kind = $request->kind();
        $decision = $request->decision();

        return $this->decisionOutcome(
            $kind->label(), $kind->value, $number, $decision,
            fn () => $decide->handle($kind, $number, $decision, $request->reason()),
        );
    }

    public function destroy(string $number, Request $request, DeleteInventoryDocument $delete): RedirectResponse
    {
        $this->assertUsableNumber($number);

        $kind = $this->inventoryKind($request);

        return $this->deleteOutcome($kind->label(), $kind->value, $number, fn () => $delete->handle($kind, $number));
    }

    public function finish(string $number, CompleteGoodsTransferRequest $complete): RedirectResponse
    {
        return $this->complete($number, true, $complete);
    }

    public function reopen(string $number, CompleteGoodsTransferRequest $complete): RedirectResponse
    {
        return $this->complete($number, false, $complete);
    }

    private function complete(string $number, bool $finished, CompleteGoodsTransferRequest $complete): RedirectResponse
    {
        $this->assertUsableNumber($number);

        $kind = InventoryKind::GoodsTransferRequests;

        return $this->transitionOutcome(
            $kind->label(), $kind->value, $number,
            $finished ? 'finish' : 'reopen',
            $finished ? 'finished' : 'reopened',
            fn () => $complete->handle($number, $finished),
        );
    }

    /**
     * Literal vendor paths share the number's segment
     * (InventoryDocumentController::NUMBER).
     */
    private function assertUsableNumber(string $number): void
    {
        abort_unless(preg_match(InventoryDocumentController::NUMBER, $number) === 1, 404);
    }
}
