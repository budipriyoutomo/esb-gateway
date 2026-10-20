<?php

namespace App\Authorization;

use App\Domain\Canonical\InventoryKind;

/**
 * Which permission guards which inventory document's writes
 * (TODO-modules.md §3). Kept out of InventoryKind so the canonical model does
 * not depend on the gateway's authorization.
 */
final class InventoryPermissions
{
    /** Authorize / reject. */
    public static function approve(InventoryKind $kind): Permission
    {
        return match ($kind) {
            InventoryKind::GoodsTransferRequests => Permission::ApproveGoodsTransferRequests,
            InventoryKind::GoodsDeliveries => Permission::ApproveGoodsDeliveries,
            InventoryKind::GoodsReceipts => Permission::ApproveGoodsReceipts,
            InventoryKind::ItemJournals => Permission::ApproveItemJournals,
            InventoryKind::SimpleTransfers => Permission::ApproveSimpleTransfers,
        };
    }

    /** Delete, create / update, attachments, and a transfer request's finish / reopen. */
    public static function manage(InventoryKind $kind): Permission
    {
        return match ($kind) {
            InventoryKind::GoodsTransferRequests => Permission::ManageGoodsTransferRequests,
            InventoryKind::GoodsDeliveries => Permission::ManageGoodsDeliveries,
            InventoryKind::GoodsReceipts => Permission::ManageGoodsReceipts,
            InventoryKind::ItemJournals => Permission::ManageItemJournals,
            InventoryKind::SimpleTransfers => Permission::ManageSimpleTransfers,
        };
    }
}
