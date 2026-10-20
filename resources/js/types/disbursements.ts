/** Mirrors App\Domain\Canonical\DisbursementSummary ([ESB-037]). */
export type DisbursementSummary = {
    provider: string;
    number: string;
    date: string | null;
    branchId: number | null;
    branchName: string | null;
    supplierId: number | null;
    supplierName: string | null;
    paymentMethod: string | null;
    reference: string | null;
    notes: string | null;
    grandTotal: number | null;
    invoiceDueDate: string | null;
    overdue: boolean | null;
    hasApproval: boolean | null;
    statusId: number | null;
    statusName: string | null;
};

/** Mirrors App\Domain\Canonical\DisbursementLine. */
export type DisbursementLine = {
    branchId: number | null;
    branchName: string | null;
    coaNo: string | null;
    coaDescription: string | null;
    description: string | null;
    amount: number | null;
};

/** Mirrors App\Domain\Canonical\DisbursementLinkedDocument. */
export type DisbursementLinkedDocument = {
    type: string;
    number: string;
    date: string | null;
    total: number | null;
    currencyName: string | null;
    statusName: string | null;
};

/**
 * Mirrors App\Domain\Canonical\Disbursement. The bank account number comes
 * masked; the carry-over stays on the server.
 */
export type Disbursement = {
    provider: string;
    number: string;
    date: string | null;
    statusId: number | null;
    statusName: string | null;
    branchId: number | null;
    branchName: string | null;
    paymentId: number | null;
    paymentName: string | null;
    cashBankCoaNo: string | null;
    supplierId: number | null;
    supplierName: string | null;
    currencyId: number | null;
    currencyName: string | null;
    currencySign: string | null;
    rate: number | null;
    creditTerm: number | null;
    invoiceDueDate: string | null;
    reference: string | null;
    notes: string | null;
    bankName: string | null;
    bankAccountName: string | null;
    maskedBankAccountNumber: string | null;
    costCenter: string | null;
    project: string | null;
    grandTotal: number | null;
    releasePaymentCode: string | null;
    lines: DisbursementLine[];
    linkedDocuments: DisbursementLinkedDocument[];
    attachmentCount: number;
    createdBy: string | null;
    createdAt: string | null;
    editedBy: string | null;
    editedAt: string | null;
    authorizedBy: string | null;
    authorizedAt: string | null;
};

/** The vendor's paymentID. */
export const PAYMENT = { cash: 1, credit: 2 } as const;

/** The statuses the vendor documents ([ESB-038]). */
export const DISBURSEMENT_STATUS = {
    new: 1,
    rejected: 2,
    authorized: 3,
    waitingForApproval: 38,
} as const;
