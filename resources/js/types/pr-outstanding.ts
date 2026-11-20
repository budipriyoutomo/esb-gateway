import type { Pagination } from '@/types/pagination';

/** App\Domain\Canonical\OutstandingStage (ADR-034). */
export type OutstandingStageValue =
    | 'not-ordered'
    | 'not-received'
    | 'partially-received'
    | 'not-as-requested';

export type OutstandingStage = {
    value: OutstandingStageValue;
    label: string;
};

/** App\Domain\Canonical\OutstandingRequestItem. */
export type OutstandingRequestItem = {
    stage: OutstandingStageValue;
    /** One number, or an order's PR numbers as the ERP sent them (comma separated). */
    purchaseRequestNumbers: string | null;
    purchaseRequestDate: string | null;
    purchaseOrderNumber: string | null;
    purchaseOrderDate: string | null;
    purchaseOrderStatus: string | null;
    requiredDate: string | null;
    branchName: string | null;
    supplierName: string | null;
    productCode: string | null;
    productName: string | null;
    uomName: string | null;
    requestedQty: number | null;
    orderedQty: number | null;
    receivedQty: number | null;
    outstandingQty: number;
    ageDays: number | null;
    overdueDays: number;
};

/** App\Domain\Canonical\OutstandingRequestReport. */
export type OutstandingRequestReport = {
    items: OutstandingRequestItem[];
    pagination: Pagination;
    stageCounts: Record<OutstandingStageValue, number>;
    overdueCount: number;
};
