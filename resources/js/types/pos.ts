/** Mirrors App\Domain\Canonical\MenuCategoryDetail ([ESB-047]). */
export type MenuCategoryDetail = {
    id: number;
    name: string;
    code: string | null;
    description: string | null;
    maxOrderQty: number | null;
    statusName: string | null;
    buttonColor: string | null;
};

/** Mirrors App\Domain\Canonical\MenuCategory ([ESB-047]). */
export type MenuCategory = {
    provider: string;
    id: number;
    name: string;
    code: string | null;
    salesAccount: string | null;
    cogsAccount: string | null;
    discountAccount: string | null;
    description: string | null;
    notes: string | null;
    statusName: string | null;
    buttonColor: string | null;
    details: MenuCategoryDetail[];
};

/** Mirrors App\Domain\Canonical\Promotion ([ESB-049]). */
export type Promotion = {
    provider: string;
    id: number;
    code: string | null;
    typeName: string | null;
    notes: string | null;
    discount: number | null;
    minSubtotal: number | null;
    startAt: string | null;
    endAt: string | null;
    shownOnEso: boolean | null;
    branches: { id: number | null; code: string | null; name: string }[];
    selfOrderPaymentMethods: string[];
    paymentMethods: string[];
    scope: {
        menuCategories: number;
        menuCategoryDetails: number;
        menus: number;
    };
};

/** One of PromotionType's cases, as PromotionFormController sends it. */
export type PromotionTypeOption = {
    id: number;
    name: string;
    fields: string[];
};

/** PromotionDraft::APPLY_TO_*. */
export const APPLY_DISCOUNT_TO = {
    menuCategory: 1,
    menuCategoryDetail: 2,
    menu: 3,
} as const;
