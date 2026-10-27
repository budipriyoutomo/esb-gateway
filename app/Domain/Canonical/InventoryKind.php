<?php

namespace App\Domain\Canonical;

/**
 * The inventory documents the gateway reads ([ESB-033], ADR-026). The value
 * is the route segment, e.g. /goods-deliveries.
 */
enum InventoryKind: string
{
    case GoodsTransferRequests = 'goods-transfer-requests';
    case GoodsDeliveries = 'goods-deliveries';
    case GoodsReceipts = 'goods-receipts';
    case ItemJournals = 'item-journals';
    case SimpleTransfers = 'simple-transfers';

    /** Singular, sentence case, e.g. "Goods delivery". */
    public function label(): string
    {
        return match ($this) {
            self::GoodsTransferRequests => 'Goods transfer request',
            self::GoodsDeliveries => 'Goods delivery',
            self::GoodsReceipts => 'Goods receipt',
            self::ItemJournals => 'Item journal',
            self::SimpleTransfers => 'Simple transfer',
        };
    }

    /** Plural, for the list, e.g. "Goods deliveries". */
    public function pluralLabel(): string
    {
        return match ($this) {
            self::GoodsTransferRequests => 'Goods transfer requests',
            self::GoodsDeliveries => 'Goods deliveries',
            self::GoodsReceipts => 'Goods receipts',
            self::ItemJournals => 'Item journals',
            self::SimpleTransfers => 'Simple transfers',
        };
    }

    /**
     * The statuses the vendor documents for this document's list filter.
     *
     * @return array<int, string>
     */
    public function statuses(): array
    {
        $common = [1 => 'New', 2 => 'Rejected', 3 => 'Authorized'];

        return match ($this) {
            self::GoodsTransferRequests => $common + [6 => 'Delivering', 8 => 'Finished'],
            // 33 "Pending": a reference not yet delivered / received (no number).
            self::GoodsDeliveries => $common + [8 => 'Finished', 27 => 'Released', 33 => 'Pending'],
            self::GoodsReceipts => $common + [4 => 'Receiving', 5 => 'Received', 8 => 'Finished', 11 => 'Invoice', 25 => 'Closed', 33 => 'Pending'],
            self::ItemJournals => $common + [38 => 'Waiting For Approval', 53 => 'Approved'],
            self::SimpleTransfers => $common,
        };
    }

    /** Whether the vendor's list filters by branch (simple transfers filter by location). */
    public function filtersByBranch(): bool
    {
        return $this !== self::SimpleTransfers;
    }

    /** Whether the vendor's list takes a free-text search. */
    public function searchable(): bool
    {
        return $this === self::GoodsTransferRequests;
    }

    /**
     * Whether the gateway creates and edits this document (S3, [ESB-035]).
     * Goods deliveries and goods receipts are created from a reference
     * document in the ERP and are not.
     */
    public function editable(): bool
    {
        return in_array($this, [self::GoodsTransferRequests, self::ItemJournals, self::SimpleTransfers], true);
    }

    /**
     * Whether files can be attached (S4, [ESB-036]). A goods receipt's only
     * upload is asset images, which the gateway does not handle.
     */
    public function takesAttachments(): bool
    {
        return $this !== self::GoodsReceipts;
    }

    /**
     * Whether the vendor's upload keeps only the "existing" URLs it is sent,
     * so an upload while there are attachments would drop them.
     */
    public function attachmentUploadReplaces(): bool
    {
        return in_array($this, [self::GoodsDeliveries, self::ItemJournals, self::SimpleTransfers], true);
    }

    /** Whether the vendor can remove every attachment (simple transfers cannot). */
    public function attachmentsRemovable(): bool
    {
        return $this->takesAttachments() && $this !== self::SimpleTransfers;
    }

    /**
     * The file types the vendor names: images for transfer requests; PDF,
     * BMP, GIF, JPEG, PNG for deliveries and simple transfers. The item
     * journal names none; PDF, JPEG, PNG are assumed.
     *
     * @return list<string>
     */
    public function attachmentExtensions(): array
    {
        return match ($this) {
            self::GoodsTransferRequests => ['jpg', 'jpeg', 'png'],
            self::GoodsDeliveries, self::SimpleTransfers => ['pdf', 'bmp', 'gif', 'jpg', 'jpeg', 'png'],
            default => ['pdf', 'jpg', 'jpeg', 'png'],
        };
    }

    /**
     * Per file: 2 MB for simple transfers (documented, 10 MB in all); 5 MB
     * elsewhere (assumed, as for supplier attachments).
     */
    public function attachmentMaxKilobytes(): int
    {
        return $this === self::SimpleTransfers ? 2048 : 5120;
    }

    /** Whether the vendor's reject takes a reason (S2); the others take no body. */
    public function rejectTakesReason(): bool
    {
        return $this === self::ItemJournals;
    }
}
