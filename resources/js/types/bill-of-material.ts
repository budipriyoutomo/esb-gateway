/**
 * Mirrors App\Domain\Canonical\BillOfMaterial, BillOfMaterialSummary, and
 * their nested DTOs.
 */

export type BillOfMaterialSummary = {
    provider: string;
    id: number | null;
    code: string | null;
    name: string;
    typeId: number | null;
    typeName: string | null;
    productName: string | null;
    uomName: string | null;
    notes: string | null;
    active: boolean | null;
};

export type BillOfMaterialSubstitute = {
    productDetailId: number;
    productCode: string | null;
    productName: string | null;
    uomName: string | null;
    order: number | null;
};

export type BillOfMaterialLine = {
    lineId: number | null;
    productDetailId: number;
    productCode: string | null;
    productName: string;
    uomName: string | null;
    qty: number | null;
    uomQty: number | null;
    conversionQty: number | null;
    lastCost: number | null;
    price: number | null;
    printGroup: string | null;
    tolerancePercent: number | null;
    yieldPercent: number | null;
    weightFactor: number | null;
    substitutes: BillOfMaterialSubstitute[];
    manufacturingSubstitutes: string[];
};

export type BillOfMaterialCost = {
    accountNo: string | null;
    description: string | null;
    total: number | null;
};

export type BillOfMaterialReplacement = {
    menuCode: string | null;
    menuName: string | null;
    productCode: string | null;
    productName: string | null;
    replacementProductCode: string | null;
    replacementProductName: string | null;
    uomName: string | null;
    qty: number | null;
};

export type BillOfMaterial = {
    provider: string;
    id: number;
    code: string | null;
    name: string;
    typeId: number | null;
    typeName: string | null;
    accessType: string | null;
    productId: number | null;
    productCode: string | null;
    productName: string | null;
    uomName: string | null;
    notes: string | null;
    active: boolean | null;
    materialCostTotal: number | null;
    otherCostTotal: number | null;
    weightFactorTotal: number | null;
    createdBy: string | null;
    createdAt: string | null;
    editedBy: string | null;
    editedAt: string | null;
    lines: BillOfMaterialLine[];
    costs: BillOfMaterialCost[];
    replacements: BillOfMaterialReplacement[];
};
