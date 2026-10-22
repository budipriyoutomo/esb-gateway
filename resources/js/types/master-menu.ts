/**
 * Mirrors App\Domain\Canonical\MasterMenu and its nested DTOs.
 */

export type MasterMenuTemplatePrice = {
    templateId: number;
    templateName: string | null;
    active: boolean | null;
    price: number | null;
};

export type MasterMenuPackageItem = {
    menuId: number;
    name: string;
    code: string | null;
    active: boolean | null;
    additionalPrice: number | null;
    defaultItem: boolean | null;
};

export type MasterMenuPackageGroup = {
    groupId: number | null;
    name: string;
    active: boolean | null;
    order: number | null;
    minQty: number | null;
    maxQty: number | null;
    notes: string | null;
    items: MasterMenuPackageItem[];
};

export type MasterMenuExtra = {
    extraId: number | null;
    name: string;
    active: boolean | null;
    minQty: number | null;
    maxQty: number | null;
    price: number | null;
};

/** An extra with the menu it belongs to (MasterMenuController::extras). */
export type MasterMenuExtraRow = MasterMenuExtra & {
    menuCode: string;
    menuName: string;
};

export type MasterMenuReference = {
    menuId: number;
    name: string | null;
    code: string | null;
};

export type MasterMenu = {
    provider: string;
    id: number | null;
    code: string;
    name: string;
    shortName: string | null;
    alternativeName: string | null;
    categoryName: string | null;
    bomName: string | null;
    active: boolean | null;
    taxLabel: string | null;
    otherTax: boolean | null;
    openPrice: boolean | null;
    printZeroValue: boolean | null;
    zeroValueText: string | null;
    salesAccount: string | null;
    cogsAccount: string | null;
    discountAccount: string | null;
    description: string | null;
    notes: string | null;
    buttonColor: string | null;
    separatePrintPackage: boolean | null;
    separateTaxCalculation: boolean | null;
    templates: MasterMenuTemplatePrice[];
    packageGroups: MasterMenuPackageGroup[];
    extras: MasterMenuExtra[];
    icons: string[];
    tags: string[];
    relatedMenus: MasterMenuReference[];
};

export function formatFlag(value: boolean | null): string {
    if (value === null) {
        return '—';
    }

    return value ? 'Yes' : 'No';
}
