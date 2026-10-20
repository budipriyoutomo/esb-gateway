<?php

namespace App\Actions\Inventory;

use App\Actions\Support\DocumentWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Domain\Canonical\InventoryKind;
use App\Integrations\Contracts\InventoryDocumentSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Finish a goods transfer request (nothing more is delivered against it) or
 * reopen a finished one ([ESB-034]), under the same lock as its decisions
 * and delete. Which statuses allow it is the ERP's call: finish from
 * Authorized / Delivering, reopen from Finished.
 */
class CompleteGoodsTransferRequest
{
    public function __construct(
        private readonly InventoryDocumentSource $documents,
        private readonly DocumentWrites $writes,
    ) {}

    /**
     * @param  bool  $finished  true finishes, false reopens.
     *
     * @throws WriteAlreadyInFlight when another write on this document is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(string $number, bool $finished): void
    {
        $this->writes->transition(
            InventoryDocuments::approvable(InventoryKind::GoodsTransferRequests),
            $this->documents->provider(),
            $number,
            $finished ? 'finish' : 'reopen',
            $finished ? AuditAction::GoodsTransferRequestFinished : AuditAction::GoodsTransferRequestReopened,
            fn () => $this->documents->completeTransferRequest($number, $finished),
        );
    }
}
