import * as goodsDeliveries from '@/routes/goods-deliveries';
import * as goodsReceipts from '@/routes/goods-receipts';
import * as goodsTransferRequests from '@/routes/goods-transfer-requests';
import * as itemJournals from '@/routes/item-journals';
import * as simpleTransfers from '@/routes/simple-transfers';
import * as goodsDeliveryAttachments from '@/routes/goods-deliveries/attachments';
import * as goodsDeliveryDecisions from '@/routes/goods-deliveries/decisions';
import * as goodsReceiptDecisions from '@/routes/goods-receipts/decisions';
import * as goodsTransferRequestAttachments from '@/routes/goods-transfer-requests/attachments';
import * as goodsTransferRequestDecisions from '@/routes/goods-transfer-requests/decisions';
import * as itemJournalAttachments from '@/routes/item-journals/attachments';
import * as itemJournalDecisions from '@/routes/item-journals/decisions';
import * as simpleTransferAttachments from '@/routes/simple-transfers/attachments';
import * as simpleTransferDecisions from '@/routes/simple-transfers/decisions';
import type {
    EditableInventoryKind,
    InventoryKind,
    InventoryParty,
} from '@/types/inventory';

type Kind = InventoryKind['value'];

/** Each kind's own routes ([ESB-033]); the pages are shared (ADR-026). */
export const inventoryRoutes = {
    'goods-transfer-requests': goodsTransferRequests,
    'goods-deliveries': goodsDeliveries,
    'goods-receipts': goodsReceipts,
    'item-journals': itemJournals,
    'simple-transfers': simpleTransfers,
} satisfies Record<Kind, { index: unknown; show: unknown; destroy: unknown }>;

const decisionRoutes = {
    'goods-transfer-requests': goodsTransferRequestDecisions,
    'goods-deliveries': goodsDeliveryDecisions,
    'goods-receipts': goodsReceiptDecisions,
    'item-journals': itemJournalDecisions,
    'simple-transfers': simpleTransferDecisions,
} satisfies Record<Kind, { store: unknown }>;

export function indexUrl(kind: Kind): string {
    return inventoryRoutes[kind].index().url;
}

export function showUrl(kind: Kind, number: string): string {
    return inventoryRoutes[kind].show(number).url;
}

const formRoutes = {
    'goods-transfer-requests': goodsTransferRequests,
    'item-journals': itemJournals,
    'simple-transfers': simpleTransfers,
} satisfies Record<
    EditableInventoryKind,
    { create: unknown; store: unknown; edit: unknown; update: unknown }
>;

export function isEditableKind(kind: Kind): kind is EditableInventoryKind {
    return kind in formRoutes;
}

/** The create / edit pages and their targets ([ESB-035]). */
export function createUrl(kind: EditableInventoryKind): string {
    return formRoutes[kind].create().url;
}

export function storeUrl(kind: EditableInventoryKind): string {
    return formRoutes[kind].store().url;
}

export function editUrl(kind: EditableInventoryKind, number: string): string {
    return formRoutes[kind].edit(number).url;
}

export function updateUrl(kind: EditableInventoryKind, number: string): string {
    return formRoutes[kind].update(number).url;
}

/** The kinds that take attachments ([ESB-036]); goods receipts do not. */
const attachmentRoutes: Partial<
    Record<Kind, { store: (number: string) => { url: string } }>
> = {
    'goods-transfer-requests': goodsTransferRequestAttachments,
    'goods-deliveries': goodsDeliveryAttachments,
    'item-journals': itemJournalAttachments,
    'simple-transfers': simpleTransferAttachments,
};

/** POST files, and DELETE all where the ERP can: the same URL. */
export function attachmentsUrl(kind: Kind, number: string): string | null {
    return attachmentRoutes[kind]?.store(number).url ?? null;
}

/** POST { decision, reason? } ([ESB-034]). */
export function decisionUrl(kind: Kind, number: string): string {
    return decisionRoutes[kind].store(number).url;
}

export function destroyUrl(kind: Kind, number: string): string {
    return inventoryRoutes[kind].destroy(number).url;
}

/** Goods transfer requests only: PATCH finish / reopen. */
export function completionUrl(
    number: string,
    operation: 'finish' | 'reopen',
): string {
    return goodsTransferRequests[operation](number).url;
}

/**
 * Goods transfer request statuses the vendor documents for each write:
 * a decision while New, finish while Authorized or Delivering, reopen while
 * Finished. The other documents' are undocumented, so the ERP decides.
 */
export const TRANSFER_REQUEST_STATUS = {
    new: 1,
    authorized: 3,
    delivering: 6,
    finished: 8,
} as const;

/** What each side of a document is called, per kind. */
export function sideLabels(kind: Kind): { from: string; to: string | null } {
    switch (kind) {
        case 'item-journals':
            return { from: 'Branch / location', to: null };
        case 'goods-receipts':
            return { from: 'From', to: 'Received at' };
        default:
            return { from: 'From', to: 'To' };
    }
}

/** Whether the kind's documents point to a reference document. */
export function hasReference(kind: Kind): boolean {
    return (
        kind === 'goods-transfer-requests' ||
        kind === 'goods-deliveries' ||
        kind === 'goods-receipts'
    );
}

/** "Outlet Senopati · Gudang Outlet", or the counterpart's name. */
export function partyLabel(party: InventoryParty | null): string | null {
    if (party === null) {
        return null;
    }

    const place = [party.branchName, party.locationName]
        .filter((part) => part !== null)
        .join(' · ');

    return party.name ?? (place === '' ? null : place);
}
