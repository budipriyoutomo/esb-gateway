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
    index as purchaseInvoicesIndex,
    show as purchaseInvoicesShow,
} from '@/routes/purchase-invoices';
import type { DocumentFilters, Pagination } from '@/types/pagination';

type PurchaseInvoiceSummary = {
    provider: string;
    number: string;
    supplierInvoiceNumber: string | null;
    // Dates are the strings the ERP sent (format not documented). formatDate()
    // formats the shapes it recognises and shows anything else as-is.
    date: string | null;
    dueDate: string | null;
    branchId: number | null;
    branchName: string | null;
    supplierId: number | null;
    supplierName: string | null;
    currencyId: number | null;
    currencySign: string | null;
    total: number | null;
    statusId: number | null;
    statusName: string | null;
    createdByName: string | null;
    notes: string | null;
};

type Filters = DocumentFilters & {
    supplierInvoiceNumber?: string;
    [key: string]: string | number | number[] | undefined;
};

type Props = {
    purchaseInvoices: PurchaseInvoiceSummary[];
    pagination: Pagination | null;
    filters: Filters;
};

const columns: DataTableColumn<PurchaseInvoiceSummary>[] = [
    {
        key: 'number',
        header: 'Number',
        className: 'font-mono text-xs',
        cell: (invoice) =>
            invoice.number !== '' ? (
                <Link
                    href={purchaseInvoicesShow(invoice.number)}
                    className="underline-offset-4 hover:underline"
                >
                    {invoice.number}
                </Link>
            ) : (
                '—'
            ),
    },
    {
        key: 'supplier',
        header: 'Supplier',
        className: 'font-medium',
        cell: (invoice) => invoice.supplierName ?? '—',
    },
    {
        key: 'supplierInvoice',
        header: 'Supplier invoice',
        className: 'text-muted-foreground font-mono text-xs',
        cell: (invoice) => invoice.supplierInvoiceNumber ?? '—',
    },
    {
        key: 'date',
        header: 'Date',
        cell: (invoice) => formatDate(invoice.date),
    },
    {
        key: 'due',
        header: 'Due',
        cell: (invoice) => formatDate(invoice.dueDate),
    },
    {
        key: 'branch',
        header: 'Branch',
        className: 'text-muted-foreground',
        cell: (invoice) => invoice.branchName ?? '—',
    },
    {
        key: 'total',
        header: 'Total',
        align: 'right',
        cell: (invoice) => formatAmount(invoice.total, invoice.currencySign),
    },
    {
        key: 'status',
        header: 'Status',
        cell: (invoice) => <StatusBadge status={invoice.statusName} />,
    },
];

export default function PurchaseInvoicesIndex({
    purchaseInvoices,
    pagination,
    filters,
}: Props) {
    const form = useFilterForm(purchaseInvoicesIndex().url, filters, [
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
            <Head title="Purchase invoices" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Purchase invoices"
                    description="Supplier invoices, read live from the ERP."
                />

                <FilterBar
                    id="pi"
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
                        <Label htmlFor="pi-supplier-invoice">
                            Supplier invoice no.
                        </Label>
                        <Input
                            id="pi-supplier-invoice"
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
                        id="pi-date"
                        label="Invoice date"
                        from={form.values.dateFrom!}
                        to={form.values.dateTo!}
                        onChange={({ from, to }) => {
                            form.set('dateFrom', from);
                            form.set('dateTo', to);
                        }}
                    />
                </FilterBar>

                <DataTable
                    caption="Purchase invoices"
                    columns={columns}
                    rows={purchaseInvoices}
                    rowKey={(invoice, index) =>
                        invoice.number !== '' ? invoice.number : `row-${index}`
                    }
                    rowHref={(invoice) =>
                        invoice.number !== ''
                            ? purchaseInvoicesShow(invoice.number).url
                            : null
                    }
                    empty="No purchase invoices matched this filter. Try a wider date range or reset the filters."
                />

                <ListPagination
                    pagination={pagination}
                    noun="purchase invoices"
                    onPageChange={form.goToPage}
                />
            </div>
        </>
    );
}
