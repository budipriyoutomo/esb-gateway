<?php

namespace App\Integrations\Contracts;

use App\Domain\Canonical\ApprovalDecision;
use App\Domain\Canonical\InventoryCarryOver;
use App\Domain\Canonical\InventoryDocument;
use App\Domain\Canonical\InventoryDocumentPage;
use App\Domain\Canonical\InventoryDocumentQuery;
use App\Domain\Canonical\InventoryDraft;
use App\Domain\Canonical\InventoryKind;
use App\Integrations\ESB\Exceptions\EsbException;

/**
 * Reads the inventory documents ([ESB-033], ADR-026) and changes their
 * state ([ESB-034]).
 */
interface InventoryDocumentSource
{
    public function provider(): string;

    /**
     * @throws EsbException
     */
    public function listDocuments(InventoryKind $kind, InventoryDocumentQuery $query): InventoryDocumentPage;

    /**
     * The document with this number, or null when the provider answers
     * successfully with none. Kind-specific extras (outstanding quantity,
     * stock check) are filled in when the provider answers them; their own
     * failure leaves them empty.
     *
     * @throws EsbException a not-found refusal surfaces as EsbNotFoundException
     */
    public function findDocument(InventoryKind $kind, string $number): ?InventoryDocument;

    /**
     * Authorize or reject the document. The reason is sent only where the
     * provider takes one (InventoryKind::rejectTakesReason()).
     *
     * @throws EsbException
     */
    public function decideDocument(InventoryKind $kind, string $number, ApprovalDecision $decision, ?string $reason = null): void;

    /**
     * @throws EsbException
     */
    public function deleteDocument(InventoryKind $kind, string $number): void;

    /**
     * Create a document (an InventoryKind::editable() one). The new number,
     * or null when the provider does not say it.
     *
     * @throws EsbException
     */
    public function createDocument(InventoryKind $kind, InventoryDraft $draft): ?string;

    /**
     * Replace a document with the draft, sending back what the form does not
     * show.
     *
     * @throws EsbException
     */
    public function updateDocument(InventoryKind $kind, string $number, InventoryDraft $draft, InventoryCarryOver $carryOver): void;

    /**
     * Upload files to a document (InventoryKind::takesAttachments()).
     *
     * @param  list<array{contents: string, filename: string}>  $files
     *
     * @throws EsbException
     */
    public function attachFiles(InventoryKind $kind, string $number, array $files): void;

    /**
     * Remove every attachment of a document (InventoryKind::attachmentsRemovable()).
     *
     * @throws EsbException
     */
    public function deleteAttachments(InventoryKind $kind, string $number): void;

    /**
     * Close a goods transfer request so nothing more is delivered against
     * it, or take that back (reopen = the vendor's "unfinished").
     *
     * @throws EsbException
     */
    public function completeTransferRequest(string $number, bool $finished): void;
}
