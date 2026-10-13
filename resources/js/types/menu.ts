/**
 * Mirrors App\Domain\Canonical\Menu.
 */
export type Menu = {
    provider: string;
    id: number | null;
    code: string;
    name: string;
    shortName: string | null;
    categoryId: number | null;
    categoryName: string | null;
    categoryDetailId: number | null;
    categoryDetailName: string | null;
    notes: string | null;
};
