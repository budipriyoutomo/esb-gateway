/**
 * Mirrors App\Domain\Canonical\PurchaseOrder, PurchaseOrderSummary, and
 * their nested DTOs.
 *
 * Dates are the strings the ERP sent. Their format is not documented, so
 * they are displayed as-is rather than parsed. Amounts are display-only: the
 * ERP is the authority on every total.
 */

export type PurchaseOrderSummary = {
    provider: string;
    number: string;
    date: string | null;
    requiredDate: string | null;
    branchId: number | null;
    branchName: string | null;
    supplierId: number | null;
    supplierName: string | null;
    currencyId: number | null;
    currencySign: string | null;
    total: number | null;
    statusId: number | null;
    statusName: string | null;
    purchaseRequestNumbers: string | null;
    createdByName: string | null;
    notes: string | null;
};

export type PurchaseOrderLine = {
    productDetailId: number | null;
    productCode: string;
    productName: string;
    qty: number | null;
    requestedQty: number | null;
    uomName: string | null;
    price: number | null;
    discount: number | null;
    discountPercent: number | null;
    total: number | null;
    notes: string | null;
};

export type PurchaseOrderLinkedDocument = {
    number: string;
    date: string | null;
    statusName: string | null;
    total: number | null;
};

export type PurchaseOrder = {
    provider: string;
    number: string;
    date: string | null;
    requiredDate: string | null;
    purchaseTypeName: string | null;
    branchId: number | null;
    branchName: string | null;
    supplierId: number | null;
    supplierName: string | null;
    supplierBranchName: string | null;
    supplierAddress: string | null;
    supplierPhone: string | null;
    contactName: string | null;
    currencyId: number | null;
    currencyName: string | null;
    currencySign: string | null;
    rate: number | null;
    dueDays: number | null;
    costCenterName: string | null;
    projectName: string | null;
    total: number | null;
    statusId: number | null;
    statusName: string | null;
    notes: string | null;
    footNote: string | null;
    purchaseRequestNumbers: string | null;
    createdByName: string | null;
    createdAt: string | null;
    editedByName: string | null;
    editedAt: string | null;
    authorizedByName: string | null;
    authorizedAt: string | null;
    // The ERP's own access flags. null means it did not say.
    canEdit: boolean | null;
    canDelete: boolean | null;
    lines: PurchaseOrderLine[];
    goodsReceipts: PurchaseOrderLinkedDocument[];
    purchaseInvoices: PurchaseOrderLinkedDocument[];
};
