import { Head, Link } from '@inertiajs/react';
import { ActiveStatusField } from '@/components/active-status-field';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { FilterBar } from '@/components/filter-bar';
import { ListPagination } from '@/components/list-pagination';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFilterForm } from '@/hooks/use-filter-form';
import { formatAmount } from '@/lib/format';
import { cn } from '@/lib/utils';
import { show as masterMenuShow } from '@/routes/master-menus';
import { index as menusIndex } from '@/routes/menus';
import type { MasterMenu } from '@/types/master-menu';
import type { Menu } from '@/types/menu';
import type { Pagination, PagingFilters } from '@/types/pagination';

type CoreFilters = PagingFilters & {
    menuName?: string;
    menuCode?: string;
    flagActive?: number;
};

type OmsFilters = { source?: 'oms'; page?: number; menuCode?: string };

type Props =
    | {
          source: 'core';
          menus: Menu[];
          pagination: Pagination | null;
          filters: CoreFilters;
      }
    | {
          source: 'oms';
          menus: MasterMenu[];
          pagination: Pagination | null;
          filters: OmsFilters;
      };

const SOURCES = [
    { value: 'core', label: 'ESB Core', href: menusIndex().url },
    {
        value: 'oms',
        label: 'ESB OMS',
        href: menusIndex({ query: { source: 'oms' } }).url,
    },
] as const;

const coreColumns: DataTableColumn<Menu>[] = [
    {
        key: 'code',
        header: 'Code',
        className: 'font-mono text-xs',
        cell: (menu) => (menu.code !== '' ? menu.code : '—'),
    },
    {
        key: 'name',
        header: 'Name',
        className: 'font-medium',
        cell: (menu) => menu.name,
    },
    {
        key: 'shortName',
        header: 'Short name',
        cell: (menu) => menu.shortName ?? '—',
    },
    {
        key: 'category',
        header: 'Category',
        cell: (menu) => menu.categoryName ?? '—',
    },
    {
        key: 'subCategory',
        header: 'Sub-category',
        cell: (menu) => menu.categoryDetailName ?? '—',
    },
    {
        key: 'notes',
        header: 'Notes',
        className: 'text-muted-foreground text-xs',
        cell: (menu) => menu.notes ?? '—',
    },
];

// The lowest template price, as a hint. Each template has its own price.
function lowestPrice(menu: MasterMenu): number | null {
    const prices = menu.templates
        .map((template) => template.price)
        .filter((price): price is number => price !== null);

    return prices.length === 0 ? null : Math.min(...prices);
}

function activeLabel(active: boolean | null): string | null {
    return active === null ? null : active ? 'Active' : 'Inactive';
}

const omsColumns: DataTableColumn<MasterMenu>[] = [
    {
        key: 'code',
        header: 'Code',
        className: 'font-mono text-xs',
        cell: (menu) =>
            menu.code !== '' ? (
                <Link
                    href={masterMenuShow(menu.code)}
                    className="underline-offset-4 hover:underline"
                >
                    {menu.code}
                </Link>
            ) : (
                '—'
            ),
    },
    {
        key: 'name',
        header: 'Name',
        className: 'font-medium',
        cell: (menu) => menu.name,
    },
    {
        key: 'category',
        header: 'Category',
        cell: (menu) => menu.categoryName ?? '—',
    },
    { key: 'tax', header: 'Tax', cell: (menu) => menu.taxLabel ?? '—' },
    {
        key: 'from',
        header: 'From',
        align: 'right',
        cell: (menu) => formatAmount(lowestPrice(menu)),
    },
    {
        key: 'status',
        header: 'Status',
        cell: (menu) => <StatusBadge status={activeLabel(menu.active)} />,
    },
];

/**
 * Each source is a different ESB call, so the tabs are links the server
 * answers (ADR-030), not client-side tabs.
 */
function SourceTabs({ current }: { current: 'core' | 'oms' }) {
    return (
        <nav aria-label="Menu source">
            <ul className="bg-muted text-muted-foreground inline-flex rounded-lg p-1">
                {SOURCES.map((source) => {
                    const active = source.value === current;

                    return (
                        <li key={source.value}>
                            <Link
                                href={source.href}
                                aria-current={active ? 'page' : undefined}
                                className={cn(
                                    'inline-flex min-h-8 items-center rounded-md px-3 text-sm font-medium pointer-coarse:min-h-11',
                                    active
                                        ? 'bg-background text-foreground shadow-sm'
                                        : 'hover:text-foreground',
                                )}
                            >
                                {source.label}
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}

function CoreMenus({
    menus,
    pagination,
    filters,
}: {
    menus: Menu[];
    pagination: Pagination | null;
    filters: CoreFilters;
}) {
    const form = useFilterForm(menusIndex().url, filters, [
        { key: 'menuName', chip: (value) => `“${value}”` },
        { key: 'menuCode', chip: (value) => `Code ${value}` },
        {
            key: 'flagActive',
            chip: (value) => (value === '1' ? 'Active' : 'Inactive'),
        },
    ]);

    return (
        <>
            <FilterBar
                id="menus"
                search={{
                    label: 'Menu name',
                    value: form.values.menuName!,
                    onChange: (value) => form.set('menuName', value),
                    placeholder: 'Menu name',
                }}
                {...form.bar}
                defaultAdvancedOpen={
                    Boolean(filters.menuCode) ||
                    filters.flagActive !== undefined
                }
            >
                <div className="grid w-full gap-1.5 sm:w-44">
                    <Label htmlFor="menus-code">Menu code</Label>
                    <Input
                        id="menus-code"
                        value={form.values.menuCode}
                        onChange={(event) => {
                            form.set('menuCode', event.target.value);
                        }}
                    />
                </div>
                <ActiveStatusField
                    id="menus-status"
                    value={form.values.flagActive!}
                    onChange={(flagActive) => {
                        // A status applies at once, as it did before.
                        form.set('flagActive', flagActive);
                        form.apply({ ...form.values, flagActive });
                    }}
                />
            </FilterBar>

            <DataTable
                caption="ESB Core menus"
                columns={coreColumns}
                rows={menus}
                rowKey={(menu, index) => `${String(menu.id)}-${String(index)}`}
                empty="No menus matched."
            />

            <ListPagination
                pagination={pagination}
                noun="menus"
                onPageChange={form.goToPage}
            />
        </>
    );
}

function OmsMenus({
    menus,
    pagination,
    filters,
}: {
    menus: MasterMenu[];
    pagination: Pagination | null;
    filters: OmsFilters;
}) {
    // The tab is in the URL and kept on every submit, so a filter, a page or
    // a reset stays on the OMS tab.
    const form = useFilterForm(
        menusIndex({ query: { source: 'oms' } }).url,
        filters,
        [{ key: 'menuCode', chip: (value) => `Code ${value}` }],
        { keep: ['source'] },
    );

    return (
        <>
            <FilterBar
                id="oms-menus"
                search={{
                    label: 'Menu code',
                    value: form.values.menuCode!,
                    onChange: (value) => form.set('menuCode', value),
                    placeholder: 'Menu code',
                }}
                {...form.bar}
            />

            <DataTable
                caption="ESB OMS menus"
                columns={omsColumns}
                rows={menus}
                rowKey={(menu) => `${String(menu.id)}-${menu.code}`}
                rowHref={(menu) =>
                    menu.code !== '' ? masterMenuShow(menu.code).url : null
                }
                empty="No menus matched."
            />

            <ListPagination
                pagination={pagination}
                noun="menus"
                onPageChange={form.goToPage}
            />
        </>
    );
}

export default function MenusIndex(props: Props) {
    return (
        <>
            <Head title="Menus" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Menus"
                    description={
                        props.source === 'oms'
                            ? 'Menus with prices per template, packages, and extras, from ESB OMS. Only active menus are listed.'
                            : 'Point-of-sale menus in ESB Core.'
                    }
                />

                <SourceTabs current={props.source} />

                {props.source === 'oms' ? (
                    <OmsMenus
                        key="oms"
                        menus={props.menus}
                        pagination={props.pagination}
                        filters={props.filters}
                    />
                ) : (
                    <CoreMenus
                        key="core"
                        menus={props.menus}
                        pagination={props.pagination}
                        filters={props.filters}
                    />
                )}
            </div>
        </>
    );
}
