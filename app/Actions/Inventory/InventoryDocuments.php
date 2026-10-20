<?php

namespace App\Actions\Inventory;

use App\Actions\Support\ApprovableDocument;
use App\Audit\AuditAction;
use App\Domain\Canonical\InventoryKind;

/**
 * How each inventory document's writes are locked and audited (see
 * DocumentWrites). One lock per document covers every write to it.
 */
final class InventoryDocuments
{
    public static function approvable(InventoryKind $kind): ApprovableDocument
    {
        return match ($kind) {
            InventoryKind::GoodsTransferRequests => new ApprovableDocument(
                lockPrefix: 'goods-transfer-request',
                authorized: AuditAction::GoodsTransferRequestAuthorized,
                rejected: AuditAction::GoodsTransferRequestRejected,
                deleted: AuditAction::GoodsTransferRequestDeleted,
                writeUnconfirmed: AuditAction::GoodsTransferRequestWriteUnconfirmed,
            ),
            InventoryKind::GoodsDeliveries => new ApprovableDocument(
                lockPrefix: 'goods-delivery',
                authorized: AuditAction::GoodsDeliveryAuthorized,
                rejected: AuditAction::GoodsDeliveryRejected,
                deleted: AuditAction::GoodsDeliveryDeleted,
                writeUnconfirmed: AuditAction::GoodsDeliveryWriteUnconfirmed,
            ),
            InventoryKind::GoodsReceipts => new ApprovableDocument(
                lockPrefix: 'goods-receipt',
                authorized: AuditAction::GoodsReceiptAuthorized,
                rejected: AuditAction::GoodsReceiptRejected,
                deleted: AuditAction::GoodsReceiptDeleted,
                writeUnconfirmed: AuditAction::GoodsReceiptWriteUnconfirmed,
            ),
            InventoryKind::ItemJournals => new ApprovableDocument(
                lockPrefix: 'item-journal',
                authorized: AuditAction::ItemJournalAuthorized,
                rejected: AuditAction::ItemJournalRejected,
                deleted: AuditAction::ItemJournalDeleted,
                writeUnconfirmed: AuditAction::ItemJournalWriteUnconfirmed,
            ),
            InventoryKind::SimpleTransfers => new ApprovableDocument(
                lockPrefix: 'simple-transfer',
                authorized: AuditAction::SimpleTransferAuthorized,
                rejected: AuditAction::SimpleTransferRejected,
                deleted: AuditAction::SimpleTransferDeleted,
                writeUnconfirmed: AuditAction::SimpleTransferWriteUnconfirmed,
            ),
        };
    }
}
