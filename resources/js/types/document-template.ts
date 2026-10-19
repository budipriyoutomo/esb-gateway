/**
 * Mirrors App\Domain\Canonical\DocumentTemplate, DocumentTemplateSummary,
 * and DocumentTemplateLine.
 */

export type DocumentTemplateSummary = {
    provider: string;
    id: number | null;
    name: string;
    typeNames: string[];
    branchNames: string[];
    active: boolean | null;
};

export type DocumentTemplateLine = {
    lineId: number | null;
    productDetailId: number | null;
    productCode: string | null;
    productName: string;
    name: string | null;
    uomName: string | null;
    categoryName: string | null;
    purposeId: number | null;
    lastCost: number | null;
    active: boolean | null;
};

export type DocumentTemplate = {
    provider: string;
    id: number;
    name: string;
    type: number | null;
    typeName: string | null;
    active: boolean | null;
    autoTransfer: boolean | null;
    allowZeroPricelist: boolean | null;
    branchIds: number[];
    costCenterIds: number[];
    lines: DocumentTemplateLine[];
    createdBy: string | null;
    createdAt: string | null;
    editedBy: string | null;
    editedAt: string | null;
    syncedAt: string | null;
};

export type DocumentTemplateTypeOption = {
    value: number;
    label: string;
};

/**
 * Mirrors App\Domain\Canonical\ProductDetailOption.
 */
export type ProductDetailOption = {
    productDetailId: number;
    productCode: string | null;
    productName: string;
    uomName: string | null;
};
