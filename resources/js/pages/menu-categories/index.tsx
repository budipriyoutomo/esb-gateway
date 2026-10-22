import { Head, Link, router } from '@inertiajs/react';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { ListPagination } from '@/components/list-pagination';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/use-permissions';
import {
    create as createRoute,
    index as indexRoute,
    show as showRoute,
} from '@/routes/menu-categories';
import type { Pagination } from '@/types/pagination';
import type { MenuCategory } from '@/types/pos';

type Props = {
    categories: MenuCategory[];
    pagination: Pagination | null;
    filters: { page?: number };
};

/**
 * POS menu categories from ESB OMS ([ESB-047]), page by page as the ESB
 * pages them -- it offers no search. Each link carries the page, where the
 * category is looked for first.
 */
export default function MenuCategoryIndex({
    categories,
    pagination,
    filters,
}: Props) {
    const { can } = usePermissions();
    const manage = can('esb.invoke') && can('menu-categories.manage');
    const page = filters.page ?? 1;
    const href = (category: MenuCategory) =>
        showRoute(category.id, { query: { page } }).url;

    const columns: DataTableColumn<MenuCategory>[] = [
        {
            key: 'name',
            header: 'Name',
            className: 'font-medium',
            cell: (category) => (
                <Link
                    href={href(category)}
                    className="underline-offset-4 hover:underline"
                >
                    {category.name}
                </Link>
            ),
        },
        {
            key: 'code',
            header: 'Code',
            className: 'font-mono text-xs',
            cell: (category) => category.code ?? '—',
        },
        {
            key: 'details',
            header: 'Category details',
            cell: (category) =>
                category.details.length === 0
                    ? '—'
                    : category.details.map((detail) => detail.name).join(', '),
        },
        {
            key: 'sales',
            header: 'Sales account',
            className: 'text-muted-foreground',
            cell: (category) => category.salesAccount ?? '—',
        },
        {
            key: 'status',
            header: 'Status',
            cell: (category) => <StatusBadge status={category.statusName} />,
        },
    ];

    return (
        <>
            <Head title="Menu categories" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Menu categories"
                    description="POS menu categories and their details, read live from ESB OMS."
                    actions={
                        manage && (
                            <Button asChild>
                                <Link href={createRoute().url}>
                                    New menu category
                                </Link>
                            </Button>
                        )
                    }
                />

                <DataTable
                    caption="Menu categories"
                    columns={columns}
                    rows={categories}
                    rowKey={(category) => String(category.id)}
                    rowHref={href}
                    empty="The ERP has no menu categories on this page."
                />

                <ListPagination
                    pagination={pagination}
                    noun="menu categories"
                    onPageChange={(next) =>
                        router.get(
                            indexRoute().url,
                            { page: next },
                            { preserveState: true, replace: true },
                        )
                    }
                />
            </div>
        </>
    );
}
