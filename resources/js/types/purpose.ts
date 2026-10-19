/**
 * Mirrors App\Domain\Canonical\Purpose and PurposeSummary.
 */

export type PurposeSummary = {
    provider: string;
    id: number | null;
    name: string;
    accountNo: string | null;
    accountName: string | null;
    appliesTo: string[];
    active: boolean | null;
};

export type Purpose = {
    provider: string;
    id: number;
    name: string;
    accountNo: string | null;
    active: boolean | null;
    transactionTypeIds: number[];
    createdBy: string | null;
    createdAt: string | null;
    editedBy: string | null;
    editedAt: string | null;
};
