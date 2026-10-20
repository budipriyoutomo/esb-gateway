import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { DataTable } from '@/components/data-table';
import { FilterBar } from '@/components/filter-bar';
import type { ActiveFilter } from '@/components/filter-bar';
import { ListPagination } from '@/components/list-pagination';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useListQuery } from '@/hooks/use-list-query';
import { usePermissions } from '@/hooks/use-permissions';
import { branchScopeLabel } from '@/lib/branch-scope';
import { formatDate } from '@/lib/format';
import {
    create as createPricelist,
    index as pricelistsIndex,
} from '@/routes/pricelists';
import { index as tempIndex, show as tempShow } from '@/routes/pricelists/temp';
import type { TempPricelistSummary } from '@/types/master-data';
import type { Pagination, PagingFilters } from '@/types/pagination';

type Filters = PagingFilters & { search?: string; statusId?: number };

type Props = {
    pricelists: TempPricelistSummary[];
    pagination: Pagination | null;
    filters: Filters;
    /** The vendor's documented statuses for this list, id → name. */
    statuses: Record<number, string>;
};

const selectClass =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px]';

export default function TempPricelistsIndex({
    pricelists,
    pagination,
    filters,
    statuses,
}: Props) {
    const list = useListQuery(tempIndex().url, filters);
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(
        filters.statusId === undefined ? '' : String(filters.statusId),
    );

    const active: ActiveFilter[] = [
        ...(filters.search
            ? [{ key: 'search', label: `“${filters.search}”` }]
            : []),
        ...(filters.statusId === undefined
            ? []
            : [
                  {
                      key: 'statusId',
                      label:
                          statuses[filters.statusId] ??
                          `Status ${filters.statusId}`,
                  },
              ]),
    ];

    const { can } = usePermissions();

    return (
        <>
            <Head title="Proposed prices" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Proposed prices"
                    description="Temporary pricelists waiting for approval or rejected, read live from the ERP."
                    back={{ href: pricelistsIndex(), label: 'Pricelist' }}
                    actions={
                        can('esb.invoke') && can('pricelists.manage') ? (
                            <Button asChild>
                                <Link href={createPricelist()}>
                                    New pricelist
                                </Link>
                            </Button>
                        ) : undefined
                    }
                />

                <FilterBar
                    id="temp-pricelist"
                    search={{
                        value: search,
                        onChange: setSearch,
                        placeholder: 'Number, supplier…',
                    }}
                    onSubmit={() => {
                        list.apply({
                            search,
                            statusId:
                                status === '' ? undefined : Number(status),
                        });
                    }}
                    active={active}
                    onRemove={(key) => {
                        if (key === 'search') {
                            setSearch('');
                        } else {
                            setStatus('');
                        }

                        list.apply({
                            ...filters,
                            page: undefined,
                            [key]: undefined,
                        });
                    }}
                    onReset={() => {
                        setSearch('');
                        setStatus('');
                        list.reset();
                    }}
                    defaultAdvancedOpen={filters.statusId !== undefined}
                >
                    <div className="grid gap-1.5">
                        <Label htmlFor="temp-pricelist-status">Status</Label>
                        <select
                            id="temp-pricelist-status"
                            className={selectClass}
                            value={status}
                            onChange={(event) => {
                                setStatus(event.target.value);
                            }}
                        >
                            <option value="">All</option>
                            {Object.entries(statuses).map(([value, label]) => (
                                <option key={value} value={value}>
                                    {label}
                                </option>
                            ))}
                        </select>
                    </div>
                </FilterBar>

                <DataTable
                    caption="Proposed prices"
                    columns={[
                        {
                            key: 'number',
                            header: 'Number',
                            className: 'font-mono text-xs',
                            cell: (pricelist) => (
                                <Link
                                    href={tempShow(pricelist.number)}
                                    className="underline-offset-4 hover:underline"
                                >
                                    {pricelist.number}
                                </Link>
                            ),
                        },
                        {
                            key: 'supplier',
                            header: 'Supplier',
                            className: 'font-medium',
                            cell: (pricelist) => pricelist.supplierName ?? '—',
                        },
                        {
                            key: 'priceDate',
                            header: 'From',
                            cell: (pricelist) =>
                                formatDate(pricelist.priceDate),
                        },
                        {
                            key: 'expireDate',
                            header: 'Until',
                            cell: (pricelist) =>
                                formatDate(pricelist.expireDate),
                        },
                        {
                            key: 'branches',
                            header: 'Branches',
                            className: 'text-muted-foreground',
                            cell: (pricelist) =>
                                branchScopeLabel(pricelist.branches),
                        },
                        {
                            key: 'status',
                            header: 'Status',
                            cell: (pricelist) => (
                                <StatusBadge status={pricelist.statusName} />
                            ),
                        },
                    ]}
                    rows={pricelists}
                    rowKey={(pricelist) => pricelist.number}
                    rowHref={(pricelist) => tempShow(pricelist.number).url}
                    empty="No proposed prices matched this filter."
                />

                <ListPagination
                    pagination={pagination}
                    noun="proposed pricelists"
                    onPageChange={list.goToPage}
                />
            </div>
        </>
    );
}

TempPricelistsIndex.layout = {
    breadcrumbs: [
        { title: 'Pricelist', href: pricelistsIndex() },
        { title: 'Proposed prices', href: tempIndex() },
    ],
};
