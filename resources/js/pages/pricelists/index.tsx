import { Head, Link } from '@inertiajs/react';
import { ExportCsvButton, exportQuery } from '@/components/export-csv-button';
import { usePermissions } from '@/hooks/use-permissions';
import { ImportLinks } from '@/components/import-links';
import { useState } from 'react';
import { DataTable } from '@/components/data-table';
import { FilterBar } from '@/components/filter-bar';
import { ListPagination } from '@/components/list-pagination';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { useListQuery } from '@/hooks/use-list-query';
import { formatAmount, formatDate } from '@/lib/format';
import { branchScopeLabel } from '@/lib/branch-scope';
import {
    exportMethod as exportRoute,
    index as pricelistsIndex,
} from '@/routes/pricelists';
import { index as tempIndex } from '@/routes/pricelists/temp';
import type { PricelistEntry } from '@/types/master-data';
import type { Pagination, PagingFilters } from '@/types/pagination';

type Props = {
    entries: PricelistEntry[];
    pagination: Pagination | null;
    filters: PagingFilters & { search?: string };
};

export default function PricelistsIndex({
    entries,
    pagination,
    filters,
}: Props) {
    const list = useListQuery(pricelistsIndex().url, filters);
    const [search, setSearch] = useState(filters.search ?? '');

    const { can } = usePermissions();

    return (
        <>
            <Head title="Pricelist" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Pricelist"
                    description="Active supplier prices, read live from the ERP."
                    actions={
                        <>
                            <ExportCsvButton
                                href={
                                    exportRoute({ query: exportQuery(filters) })
                                        .url
                                }
                            />
                            {
                                <>
                                    <ImportLinks
                                        canImport={
                                            can('esb.invoke') &&
                                            can('pricelists.manage')
                                        }
                                        imports={[
                                            {
                                                kind: 'pricelists',
                                                label: 'Import',
                                            },
                                        ]}
                                    />
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={tempIndex()}>
                                            Proposed prices
                                        </Link>
                                    </Button>
                                </>
                            }
                        </>
                    }
                />

                <FilterBar
                    id="pricelist"
                    search={{
                        value: search,
                        onChange: setSearch,
                        placeholder: 'Product, supplier…',
                    }}
                    onSubmit={() => {
                        list.apply({ search });
                    }}
                    active={
                        filters.search
                            ? [{ key: 'search', label: `“${filters.search}”` }]
                            : []
                    }
                    onRemove={() => {
                        setSearch('');
                        list.apply({
                            ...filters,
                            page: undefined,
                            search: undefined,
                        });
                    }}
                    onReset={() => {
                        setSearch('');
                        list.reset();
                    }}
                />

                <DataTable
                    caption="Active pricelist"
                    columns={[
                        {
                            key: 'product',
                            header: 'Product',
                            cell: (entry) => (
                                <>
                                    <div className="font-medium">
                                        {entry.productName ?? '—'}
                                    </div>
                                    {entry.productCode && (
                                        <div className="text-muted-foreground font-mono text-xs">
                                            {entry.productCode}
                                        </div>
                                    )}
                                </>
                            ),
                        },
                        {
                            key: 'unit',
                            header: 'Unit',
                            cell: (entry) => entry.unit ?? '—',
                        },
                        {
                            key: 'supplier',
                            header: 'Supplier',
                            cell: (entry) => entry.supplierName ?? '—',
                        },
                        {
                            key: 'price',
                            header: 'Price',
                            align: 'right',
                            cell: (entry) =>
                                formatAmount(entry.price, entry.currencyName),
                        },
                        {
                            key: 'valid',
                            header: 'Valid',
                            className: 'text-muted-foreground',
                            cell: (entry) =>
                                entry.expireDate
                                    ? `${formatDate(entry.priceDate)} – ${formatDate(entry.expireDate)}`
                                    : `from ${formatDate(entry.priceDate)}`,
                        },
                        {
                            key: 'branches',
                            header: 'Branches',
                            className: 'text-muted-foreground',
                            cell: (entry) => branchScopeLabel(entry.branches),
                        },
                    ]}
                    rows={entries}
                    rowKey={(entry, index) =>
                        entry.rowId === null
                            ? `row-${index}`
                            : String(entry.rowId)
                    }
                    empty="No prices matched this search. Try another product or supplier."
                />

                <ListPagination
                    pagination={pagination}
                    noun="prices"
                    onPageChange={list.goToPage}
                />
            </div>
        </>
    );
}
