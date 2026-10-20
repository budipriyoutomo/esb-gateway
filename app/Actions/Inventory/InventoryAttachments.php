<?php

namespace App\Actions\Inventory;

use App\Actions\Support\DocumentWrites;
use App\Actions\Support\WriteAlreadyInFlight;
use App\Audit\AuditAction;
use App\Domain\Canonical\InventoryKind;
use App\Integrations\Contracts\InventoryDocumentSource;
use App\Integrations\ESB\Exceptions\EsbException;
use LogicException;

/**
 * Attach files to an inventory document, or remove all of them
 * ([ESB-036]), under the document's lock. The audit names each file with
 * its size and SHA-256.
 */
class InventoryAttachments
{
    public function __construct(
        private readonly InventoryDocumentSource $documents,
        private readonly DocumentWrites $writes,
    ) {}

    /**
     * @param  list<array{contents: string, filename: string}>  $files
     *
     * @throws WriteAlreadyInFlight when another write on this document is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function attach(InventoryKind $kind, string $number, array $files): void
    {
        $this->writes->transition(
            InventoryDocuments::approvable($kind),
            $this->documents->provider(),
            $number,
            'attach',
            $this->audit($kind, uploaded: true),
            fn () => $this->documents->attachFiles($kind, $number, $files),
            ['files' => array_map(static fn (array $file): array => [
                'filename' => $file['filename'],
                'bytes' => strlen($file['contents']),
                'sha256' => hash('sha256', $file['contents']),
            ], $files)],
        );
    }

    /**
     * @throws WriteAlreadyInFlight when another write on this document is still running
     * @throws EsbException when the ESB call fails; see mayHaveBeenApplied()
     */
    public function delete(InventoryKind $kind, string $number): void
    {
        $this->writes->transition(
            InventoryDocuments::approvable($kind),
            $this->documents->provider(),
            $number,
            'detach',
            $this->audit($kind, uploaded: false),
            fn () => $this->documents->deleteAttachments($kind, $number),
        );
    }

    private function audit(InventoryKind $kind, bool $uploaded): AuditAction
    {
        return match ([$kind, $uploaded]) {
            [InventoryKind::GoodsTransferRequests, true] => AuditAction::GoodsTransferRequestAttachmentsUploaded,
            [InventoryKind::GoodsTransferRequests, false] => AuditAction::GoodsTransferRequestAttachmentsDeleted,
            [InventoryKind::GoodsDeliveries, true] => AuditAction::GoodsDeliveryAttachmentsUploaded,
            [InventoryKind::GoodsDeliveries, false] => AuditAction::GoodsDeliveryAttachmentsDeleted,
            [InventoryKind::ItemJournals, true] => AuditAction::ItemJournalAttachmentsUploaded,
            [InventoryKind::ItemJournals, false] => AuditAction::ItemJournalAttachmentsDeleted,
            [InventoryKind::SimpleTransfers, true] => AuditAction::SimpleTransferAttachmentsUploaded,
            default => throw new LogicException("{$kind->value} attachments are not changed through the gateway."),
        };
    }
}
