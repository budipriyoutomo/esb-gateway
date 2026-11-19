/** Closing recaps from sync-api Maharasa and their ERP mappings (ADR-032). */

export type ClosingItemSource = 'closing' | 'pos_only';

export type ClosingStockKind = 'waste' | 'adjustment' | 'compensation';

export type ClosingRecapStatus =
    | 'received'
    | 'prepared'
    | 'sent'
    | 'attention'
    | 'revised';

/**
 * What a recap still needs before it can be prepared (ClosingMappingSet).
 * The last two hold back only its sales: payments sync-api has not sent,
 * and payment methods with no ERP account.
 */
export type ClosingGap = {
    type:
        | 'outlet'
        | 'item-menu'
        | 'item-bom'
        | 'purpose'
        | 'sales-payments'
        | 'payment-method';
    key: string;
    label: string;
    message: string;
};

export type ClosingOutletMapping = {
    branchId: number;
    branchCode: string;
    branchName: string;
    locationId: number;
    locationName: string;
};

export type ClosingItemMapping = {
    esbMenuId: number | null;
    esbMenuCode: string | null;
    esbMenuName: string | null;
    bomId: number | null;
    bomName: string | null;
};

export type ClosingPurposeMapping = {
    purposeId: number;
    purposeName: string;
    direction: -1 | 1;
};

export type ClosingPaymentMapping = {
    paymentMethod: string;
    coaNo: string;
    coaName: string | null;
    merchantDiscountRate: string;
};

export const SOURCE_LABELS: Record<ClosingItemSource, string> = {
    closing: 'Closing menu',
    pos_only: 'POS product',
};
