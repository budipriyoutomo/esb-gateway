import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { DateRangeField } from '@/components/date-range-field';
import { FilterBar } from '@/components/filter-bar';
import type { ActiveFilter } from '@/components/filter-bar';
import { ListPagination } from '@/components/list-pagination';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { useListQuery } from '@/hooks/use-list-query';
import { formatAmount, formatDate } from '@/lib/format';
import {
    index as purchaseOrdersIndex,
    show as purchaseOrdersShow,
} from '@/routes/purchase-orders';
import type { DocumentFilters, Pagination } from '@/types/pagination';
import type { PurchaseOrderSummary } from '@/types/purchase-order';

type Filters = DocumentFilters & {
    requiredDateFrom?: string;
    requiredDateTo?: string;
    [key: string]: string | number | number[] | undefined;
};

type Props = {
    purchaseOrders: PurchaseOrderSummary[];
    pagination: Pagination | null;
    filters: Filters;
};

const columns: DataTableColumn<PurchaseOrderSummary>[] = [
    {
        key: 'number',
        header: 'Number',
        className: 'font-mono text-xs',
        cell: (order) =>
            order.number !== '' ? (
                <Link
                    href={purchaseOrdersShow(order.number)}
                    className="underline-offset-4 hover:underline"
                >
                    {order.number}
                </Link>
            ) : (
                '—'
            ),
    },
    {
        key: 'supplier',
        header: 'Supplier',
        className: 'font-medium',
        cell: (order) => order.supplierName ?? '—',
    },
    { key: 'date', header: 'Date', cell: (order) => formatDate(order.date) },
    {
        key: 'required',
        header: 'Required',
        cell: (order) => formatDate(order.requiredDate),
    },
    {
        key: 'branch',
        header: 'Branch',
        className: 'text-muted-foreground',
        cell: (order) => order.branchName ?? '—',
    },
    {
        key: 'total',
        header: 'Total',
        align: 'right',
        cell: (order) => formatAmount(order.total, order.currencySign),
    },
    {
        key: 'status',
        header: 'Status',
        cell: (order) => <StatusBadge status={order.statusName} />,
    },
];

const DATE_FILTERS: { key: keyof Filters & string; label: string }[] = [
    { key: 'dateFrom', label: 'Order date from' },
    { key: 'dateTo', label: 'Order date to' },
    { key: 'requiredDateFrom', label: 'Required from' },
    { key: 'requiredDateTo', label: 'Required to' },
];

export default function PurchaseOrdersIndex({
    purchaseOrders,
    pagination,
    filters,
}: Props) {
    const list = useListQuery(purchaseOrdersIndex().url, filters);
    const [search, setSearch] = useState(filters.search ?? '');
    const [orderDate, setOrderDate] = useState({
        from: filters.dateFrom ?? '',
        to: filters.dateTo ?? '',
    });
    const [requiredDate, setRequiredDate] = useState({
        from: filters.requiredDateFrom ?? '',
        to: filters.requiredDateTo ?? '',
    });

    // Chips describe what the server applied, not what is being typed.
    const active: ActiveFilter[] = [
        ...(filters.search
            ? [{ key: 'search', label: `“${filters.search}”` }]
            : []),
        ...DATE_FILTERS.flatMap(({ key, label }) => {
            const value = filters[key];

            return typeof value === 'string' && value !== ''
                ? [{ key, label: `${label} ${formatDate(value)}` }]
                : [];
        }),
    ];
    const advancedActive = DATE_FILTERS.some(({ key }) => filters[key]);

    // The fields keep their own state across visits (preserveState), so a
    // removed filter is cleared in its field as well as in the query.
    function clearField(key: string) {
        if (key === 'search') {
            setSearch('');
        } else if (key === 'dateFrom' || key === 'dateTo') {
            setOrderDate((range) => ({
                ...range,
                [key === 'dateFrom' ? 'from' : 'to']: '',
            }));
        } else if (key === 'requiredDateFrom' || key === 'requiredDateTo') {
            setRequiredDate((range) => ({
                ...range,
                [key === 'requiredDateFrom' ? 'from' : 'to']: '',
            }));
        }
    }

    return (
        <>
            <Head title="Purchase orders" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Purchase orders"
                    description="Orders to suppliers, read live from the ERP."
                />

                <FilterBar
                    id="po"
                    search={{
                        value: search,
                        onChange: setSearch,
                        placeholder: 'Number, supplier, notes…',
                    }}
                    onSubmit={() => {
                        list.apply({
                            search,
                            dateFrom: orderDate.from,
                            dateTo: orderDate.to,
                            requiredDateFrom: requiredDate.from,
                            requiredDateTo: requiredDate.to,
                        });
                    }}
                    active={active}
                    onRemove={(key) => {
                        clearField(key);
                        list.apply({
                            ...filters,
                            page: undefined,
                            [key]: undefined,
                        });
                    }}
                    onReset={() => {
                        setSearch('');
                        setOrderDate({ from: '', to: '' });
                        setRequiredDate({ from: '', to: '' });
                        list.reset();
                    }}
                    defaultAdvancedOpen={advancedActive}
                >
                    <DateRangeField
                        id="po-date"
                        label="Order date"
                        from={orderDate.from}
                        to={orderDate.to}
                        onChange={setOrderDate}
                    />
                    <DateRangeField
                        id="po-required"
                        label="Required date"
                        from={requiredDate.from}
                        to={requiredDate.to}
                        onChange={setRequiredDate}
                    />
                </FilterBar>

                <DataTable
                    caption="Purchase orders"
                    columns={columns}
                    rows={purchaseOrders}
                    rowKey={(order, index) =>
                        order.number !== '' ? order.number : `row-${index}`
                    }
                    rowHref={(order) =>
                        order.number !== ''
                            ? purchaseOrdersShow(order.number).url
                            : null
                    }
                    empty="No purchase orders matched this filter. Try a wider date range or reset the filters."
                />

                <ListPagination
                    pagination={pagination}
                    noun="purchase orders"
                    onPageChange={list.goToPage}
                />
            </div>
        </>
    );
}

PurchaseOrdersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Purchase orders',
            href: purchaseOrdersIndex(),
        },
    ],
};
