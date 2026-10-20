<?php

namespace App\Actions\Inventory;

use App\Actions\Support\DocumentWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Domain\Canonical\InventoryKind;
use App\Integrations\Contracts\InventoryDocumentSource;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Delete an inventory document in the ERP, on behalf of a person
 * ([ESB-034]). The write and audit rules are DocumentWrites'.
 */
class DeleteInventoryDocument
{
    public function __construct(
        private readonly InventoryDocumentSource $documents,
        private readonly DocumentWrites $writes,
    ) {}

    /**
     * @throws WriteAlreadyInFlight when another write on this document is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function handle(InventoryKind $kind, string $number): void
    {
        $this->writes->delete(
            InventoryDocuments::approvable($kind), $this->documents->provider(), $number,
            fn () => $this->documents->deleteDocument($kind, $number),
        );
    }
}
