import { Head, Link } from '@inertiajs/react';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { DateRangeField } from '@/components/date-range-field';
import { FilterBar } from '@/components/filter-bar';
import { ListPagination } from '@/components/list-pagination';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFilterForm } from '@/hooks/use-filter-form';
import { formatAmount, formatDate } from '@/lib/format';
import {
    index as simplePurchasesIndex,
    show as simplePurchasesShow,
} from '@/routes/simple-purchases';
import type { DocumentFilters, Pagination } from '@/types/pagination';
import type { SimplePurchaseSummary } from '@/types/simple-purchase';

type Filters = DocumentFilters & {
    supplierInvoiceNumber?: string;
    [key: string]: string | number | undefined;
};

type Props = {
    simplePurchases: SimplePurchaseSummary[];
    pagination: Pagination | null;
    filters: Filters;
};

const columns: DataTableColumn<SimplePurchaseSummary>[] = [
    {
        key: 'number',
        header: 'Number',
        className: 'font-mono text-xs',
        cell: (purchase) =>
            purchase.number !== '' ? (
                <Link
                    href={simplePurchasesShow(purchase.number)}
                    className="underline-offset-4 hover:underline"
                >
                    {purchase.number}
                </Link>
            ) : (
                '—'
            ),
    },
    {
        key: 'supplier',
        header: 'Supplier',
        className: 'font-medium',
        cell: (purchase) => purchase.supplierName ?? '—',
    },
    {
        key: 'supplierInvoice',
        header: 'Supplier invoice',
        className: 'text-muted-foreground font-mono text-xs',
        cell: (purchase) => purchase.supplierInvoiceNumber ?? '—',
    },
    {
        key: 'date',
        header: 'Date',
        cell: (purchase) => formatDate(purchase.date),
    },
    {
        key: 'branch',
        header: 'Branch',
        className: 'text-muted-foreground',
        cell: (purchase) => purchase.branchName ?? '—',
    },
    {
        key: 'total',
        header: 'Total',
        align: 'right',
        cell: (purchase) => formatAmount(purchase.total, purchase.currencySign),
    },
    {
        key: 'status',
        header: 'Status',
        cell: (purchase) => <StatusBadge status={purchase.statusName} />,
    },
];

export default function SimplePurchasesIndex({
    simplePurchases,
    pagination,
    filters,
}: Props) {
    const form = useFilterForm(simplePurchasesIndex().url, filters, [
        { key: 'search', chip: (value) => `“${value}”` },
        {
            key: 'supplierInvoiceNumber',
            chip: (value) => `Supplier invoice ${value}`,
        },
        { key: 'dateFrom', chip: (value) => `From ${formatDate(value)}` },
        { key: 'dateTo', chip: (value) => `To ${formatDate(value)}` },
    ]);

    return (
        <>
            <Head title="Simple purchases" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Simple purchases"
                    description="Purchases bought and paid for in one step, read live from the ERP."
                />

                <FilterBar
                    id="sp"
                    search={{
                        value: form.values.search!,
                        onChange: (value) => form.set('search', value),
                        placeholder: 'Number, supplier, notes…',
                    }}
                    {...form.bar}
                    defaultAdvancedOpen={Boolean(
                        filters.supplierInvoiceNumber ||
                        filters.dateFrom ||
                        filters.dateTo,
                    )}
                >
                    <div className="grid w-full gap-1.5 sm:w-48">
                        <Label htmlFor="sp-supplier-invoice">
                            Supplier invoice no.
                        </Label>
                        <Input
                            id="sp-supplier-invoice"
                            value={form.values.supplierInvoiceNumber}
                            maxLength={50}
                            onChange={(event) => {
                                form.set(
                                    'supplierInvoiceNumber',
                                    event.target.value,
                                );
                            }}
                        />
                    </div>
                    <DateRangeField
                        id="sp-date"
                        label="Date"
                        from={form.values.dateFrom!}
                        to={form.values.dateTo!}
                        onChange={({ from, to }) => {
                            form.set('dateFrom', from);
                            form.set('dateTo', to);
                        }}
                    />
                </FilterBar>

                <DataTable
                    caption="Simple purchases"
                    columns={columns}
                    rows={simplePurchases}
                    rowKey={(purchase, index) =>
                        purchase.number !== ''
                            ? purchase.number
                            : `row-${index}`
                    }
                    rowHref={(purchase) =>
                        purchase.number !== ''
                            ? simplePurchasesShow(purchase.number).url
                            : null
                    }
                    empty="No simple purchases matched this filter. Try a wider date range or reset the filters."
                />

                <ListPagination
                    pagination={pagination}
                    noun="simple purchases"
                    onPageChange={form.goToPage}
                />
            </div>
        </>
    );
}
