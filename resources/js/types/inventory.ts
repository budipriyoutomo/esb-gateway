/**
 * Mirrors App\Domain\Canonical\InventoryDocument(Summary) and their nested
 * DTOs ([ESB-033], ADR-026). A field a document does not have is null.
 * Quantities are display-only; 0 is a real quantity.
 */

/** Mirrors App\Domain\Canonical\InventoryKind, as the controller describes it. */
export type InventoryKind = {
    value:
        | 'goods-transfer-requests'
        | 'goods-deliveries'
        | 'goods-receipts'
        | 'item-journals'
        | 'simple-transfers';
    label: string;
    pluralLabel: string;
    statuses: { id: number; name: string }[];
    filtersByBranch: boolean;
    searchable: boolean;
    /** Only the item journal's reject takes a reason (optional). */
    rejectTakesReason: boolean;
    /** Created and edited through the gateway (S3, [ESB-035]). */
    editable: boolean;
    /** Files can be attached here ([ESB-036]); null when not. */
    attachments: {
        /** The ERP keeps only what it is sent back: attach while there are none. */
        uploadReplaces: boolean;
        removable: boolean;
        extensions: string[];
    } | null;
};

/** The kinds the gateway creates and edits. */
export type EditableInventoryKind =
    | 'goods-transfer-requests'
    | 'item-journals'
    | 'simple-transfers';

/** Mirrors App\Domain\Canonical\InventoryCarryOver. */
export type InventoryCarryOver = {
    purchaseRequestNumbers: string[];
    costCenterId: number | null;
    projectId: number | null;
    requestTemplateId: number | null;
    /** A document that moves assets is not edited through the gateway. */
    hasAssets: boolean;
};

export type InventoryParty = {
    branchId: number | null;
    branchName: string | null;
    locationId: number | null;
    locationName: string | null;
    name: string | null;
    address: string | null;
    phone: string | null;
};

export type InventoryDocumentSummary = {
    provider: string;
    kind: InventoryKind['value'];
    number: string;
    date: string | null;
    statusId: number | null;
    statusName: string | null;
    from: InventoryParty | null;
    to: InventoryParty | null;
    transactionType: string | null;
    reference: string | null;
    notes: string | null;
    createdBy: string | null;
};

export type InventoryLine = {
    productDetailId: number | null;
    productCode: string | null;
    productName: string | null;
    unit: string | null;
    qty: number | null;
    requestedQty: number | null;
    availableQty: number | null;
    stockQty: number | null;
    pendingQty: number | null;
    receivedQty: number | null;
    outstandingQty: number | null;
    cost: number | null;
    notes: string | null;
    purpose: string | null;
    purposeId: number | null;
};

export type InventoryLinkedDocument = {
    type: string;
    number: string;
    date: string | null;
    statusName: string | null;
};

export type InventoryDocument = Omit<InventoryDocumentSummary, 'createdBy'> & {
    referenceDate: string | null;
    costCenter: string | null;
    project: string | null;
    lines: InventoryLine[];
    linkedDocuments: InventoryLinkedDocument[];
    attachmentCount: number;
    /** From the stock check; null when not asked or not answered. */
    stockUsed: boolean | null;
    createdBy: string | null;
    createdAt: string | null;
    editedBy: string | null;
    editedAt: string | null;
    authorizedBy: string | null;
    authorizedAt: string | null;
    /** The ERP's own answer (goods transfer requests); null = not said. */
    canEdit: boolean | null;
    canDelete: boolean | null;
    carryOver: InventoryCarryOver;
};
