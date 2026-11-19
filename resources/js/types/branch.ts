/** Mirrors App\Domain\Canonical\BranchSummary ([ESB-051]). */
export type BranchSummary = {
    provider: string;
    id: number;
    name: string;
    code: string | null;
};

/** Mirrors App\Domain\Canonical\BranchDetail ([ESB-051]). */
export type BranchDetail = {
    provider: string;
    id: number;
    name: string;
    code: string | null;
    externalCode: string | null;
    esbOrderName: string | null;
    branchTypeId: number | null;
    brandId: number | null;
    active: boolean | null;
    address: string | null;
    phone: string | null;
    postalCode: string | null;
    latitude: string | null;
    longitude: string | null;
    openDate: string | null;
    closeDate: string | null;
    taxName: string | null;
    taxValue: number | null;
    additionalTaxName: string | null;
    additionalTaxValue: number | null;
    vatName: string | null;
    timezoneId: number | null;
    posVersion: string | null;
    licences: {
        cashier: number | null;
        order: number | null;
        tab: number | null;
        serverOnly: number | null;
    };
    printingHeader: string | null;
    printingFooter: string | null;
    createdBy: string | null;
    createdAt: string | null;
    editedBy: string | null;
    editedAt: string | null;
};
