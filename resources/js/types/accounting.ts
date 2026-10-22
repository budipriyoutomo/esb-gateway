/** Mirrors App\Domain\Canonical\JournalLine ([ESB-041], [ESB-043]). */
export type JournalLine = {
    id: number | null;
    branchId: number | null;
    branchName: string | null;
    coaNo: string | null;
    coaDescription: string | null;
    costCenter: string | null;
    project: string | null;
    currencyName: string | null;
    currencyId: number | null;
    rate: number | null;
    debit: number | null;
    credit: number | null;
    notes: string | null;
};

/** Mirrors App\Domain\Canonical\GeneralJournalEntry ([ESB-041]). */
export type GeneralJournalEntry = {
    provider: string;
    journalHeadId: number;
    date: string | null;
    transactionType: string | null;
    reference: string | null;
    notes: string | null;
    viewableInErp: boolean | null;
};

/** Mirrors App\Domain\Canonical\LedgerEntry ([ESB-042]). */
export type LedgerEntry = {
    date: string | null;
    branchId: number | null;
    branchName: string | null;
    coaNo: string;
    coaDescription: string | null;
    transactionType: string | null;
    reference: string | null;
    notes: string | null;
    info: string | null;
    debit: number | null;
    credit: number | null;
    costCenter: string | null;
    project: string | null;
    createdAt: string | null;
};

/** Mirrors App\Domain\Canonical\LedgerSummaryRow ([ESB-042]). */
export type LedgerSummaryRow = {
    date: string | null;
    branchId: number | null;
    branchCode: string | null;
    branchName: string | null;
    accountNo: string;
    accountDescription: string | null;
    debit: number | null;
    credit: number | null;
};

/** Mirrors App\Domain\Canonical\MemorialJournalSummary ([ESB-043]). */
export type MemorialJournalSummary = {
    provider: string;
    number: string;
    date: string | null;
    notes: string | null;
    hasApproval: boolean | null;
    createdBy: string | null;
    statusId: number | null;
    statusName: string | null;
};

/** Mirrors App\Domain\Canonical\MemorialJournal ([ESB-043]). */
export type MemorialJournal = {
    provider: string;
    number: string;
    date: string | null;
    notes: string | null;
    hasApproval: boolean | null;
    statusId: number | null;
    statusName: string | null;
    lines: JournalLine[];
    attachmentCount: number;
    createdBy: string | null;
    createdAt: string | null;
    editedBy: string | null;
    editedAt: string | null;
    authorizedBy: string | null;
    authorizedAt: string | null;
};

/** The memorial journal statuses the vendor documents ([ESB-043]). */
export const MEMORIAL_JOURNAL_STATUS = {
    new: 1,
    rejected: 2,
    authorized: 3,
    waitingForApproval: 38,
} as const;
