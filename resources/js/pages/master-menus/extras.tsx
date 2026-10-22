import { Head, Link } from '@inertiajs/react';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { FilterBar } from '@/components/filter-bar';
import { ListPagination } from '@/components/list-pagination';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { useFilterForm } from '@/hooks/use-filter-form';
import { formatAmount, formatQuantity } from '@/lib/format';
import {
    extras as extrasRoute,
    show as menusShow,
} from '@/routes/master-menus';
import type { MasterMenuExtraRow } from '@/types/master-menu';
import type { Pagination } from '@/types/pagination';

type Props = {
    extras: MasterMenuExtraRow[];
    pagination: Pagination | null;
    filters: { page?: number; menuCode?: string };
};

function activeLabel(active: boolean | null): string | null {
    return active === null ? null : active ? 'Active' : 'Inactive';
}

const columns: DataTableColumn<MasterMenuExtraRow>[] = [
    {
        key: 'menu',
        header: 'Menu code',
        className: 'font-mono text-xs',
        cell: (extra) =>
            extra.menuCode !== '' ? (
                <Link
                    href={menusShow(extra.menuCode)}
                    className="underline-offset-4 hover:underline"
                >
                    {extra.menuCode}
                </Link>
            ) : (
                '—'
            ),
    },
    { key: 'menuName', header: 'Menu', cell: (extra) => extra.menuName },
    {
        key: 'name',
        header: 'Extra',
        className: 'font-medium',
        cell: (extra) => extra.name,
    },
    {
        key: 'price',
        header: 'Price',
        align: 'right',
        cell: (extra) => formatAmount(extra.price),
    },
    {
        key: 'qty',
        header: 'Qty',
        align: 'right',
        cell: (extra) =>
            `${formatQuantity(extra.minQty)}–${formatQuantity(extra.maxQty)}`,
    },
    {
        key: 'status',
        header: 'Status',
        cell: (extra) => <StatusBadge status={activeLabel(extra.active)} />,
    },
];

/**
 * Extras across menus, read-only. ESB documents no extra endpoint, so this
 * is the extras of one page of Get Menu -- paged by menu, not by extra.
 */
export default function MasterMenuExtras({
    extras,
    pagination,
    filters,
}: Props) {
    const form = useFilterForm(extrasRoute().url, filters, [
        { key: 'menuCode', chip: (value) => `Code ${value}` },
    ]);

    return (
        <>
            <Head title="Menu extras" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Menu extras"
                    description="Add-ons of the menus on each page of the ESB OMS master menu. Extras are managed in the ERP."
                />

                <FilterBar
                    id="master-menu-extras"
                    search={{
                        label: 'Menu code',
                        value: form.values.menuCode!,
                        onChange: (value) => form.set('menuCode', value),
                        placeholder: 'Menu code',
                    }}
                    {...form.bar}
                />

                <DataTable
                    caption="Menu extras"
                    columns={columns}
                    rows={extras}
                    rowKey={(extra, index) =>
                        `${extra.menuCode}-${String(extra.extraId)}-${String(index)}`
                    }
                    rowHref={(extra) =>
                        extra.menuCode !== ''
                            ? menusShow(extra.menuCode).url
                            : null
                    }
                    empty="No extras on this page of menus."
                />

                <ListPagination
                    pagination={pagination}
                    noun="menus"
                    onPageChange={form.goToPage}
                />
            </div>
        </>
    );
}
