/**
 * Shapes of the document template import pages (ADR-023), as
 * DocumentTemplateImportController sends them.
 */

export type ImportEntryStatus =
    | 'invalid'
    | 'pending'
    | 'ready'
    | 'blocked'
    | 'syncing'
    | 'synced'
    | 'failed'
    | 'unconfirmed';

export type ImportStatus = 'idle' | 'validating' | 'syncing';

export type ImportLine = {
    rowNumber: number;
    productCode: string;
    productName: string;
    unit: string;
    category: string;
    productDetailId: number | null;
};

export type ImportEntry = {
    id: number;
    position: number;
    name: string;
    typeName: string | null;
    branchCodes: string[];
    status: ImportEntryStatus;
    problems: string[];
    esbTemplateId: number | null;
    lines: ImportLine[];
};

export type DocumentTemplateImport = {
    id: number;
    filename: string;
    status: ImportStatus;
    problems: string[];
    lastError: string | null;
    uploadedBy: string | null;
    createdAt: string | null;
    validatedAt: string | null;
    syncedAt: string | null;
};

export type ImportSummary = {
    id: number;
    filename: string;
    status: ImportStatus;
    uploadedBy: string | null;
    createdAt: string | null;
    templates: number;
    counts: Partial<Record<ImportEntryStatus, number>>;
};

export const ENTRY_STATUS_LABELS: Record<ImportEntryStatus, string> = {
    invalid: 'Invalid in file',
    pending: 'Not validated',
    ready: 'Ready',
    blocked: 'Blocked',
    syncing: 'Sending',
    synced: 'In ERP',
    failed: 'Refused by ERP',
    unconfirmed: 'Unconfirmed',
};

export const ENTRY_STATUS_VARIANTS: Record<
    ImportEntryStatus,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    invalid: 'destructive',
    pending: 'outline',
    ready: 'secondary',
    blocked: 'destructive',
    syncing: 'outline',
    synced: 'default',
    failed: 'destructive',
    unconfirmed: 'destructive',
};
