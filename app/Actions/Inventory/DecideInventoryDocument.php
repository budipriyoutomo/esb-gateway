<?php

namespace App\Actions\Inventory;

use App\Actions\Support\DocumentWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Domain\Canonical\ApprovalDecision;
use App\Domain\Canonical\InventoryKind;
use App\Integrations\Contracts\InventoryDocumentSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Authorize or reject an inventory document in the ERP, on behalf of a
 * person ([ESB-034]). The write and audit rules are DocumentWrites'.
 */
class DecideInventoryDocument
{
    public function __construct(
        private readonly InventoryDocumentSource $documents,
        private readonly DocumentWrites $writes,
    ) {}

    /**
     * @param  string|null  $reason  Sent and audited only for a rejection of a
     *                               kind whose reject takes one.
     *
     * @throws WriteAlreadyInFlight when another write on this document is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(InventoryKind $kind, string $number, ApprovalDecision $decision, ?string $reason = null): void
    {
        $this->writes->decide(
            InventoryDocuments::approvable($kind), $this->documents->provider(), $number, $decision,
            $kind->rejectTakesReason() ? $reason : null,
            fn (?string $reason) => $this->documents->decideDocument($kind, $number, $decision, $reason),
        );
    }
}
