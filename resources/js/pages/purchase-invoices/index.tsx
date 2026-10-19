import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    index as purchaseInvoicesIndex,
    show as purchaseInvoicesShow,
} from '@/routes/purchase-invoices';
import type { DocumentFilters, Pagination } from '@/types/pagination';
import { formatAmount, formatDate } from '@/lib/format';

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

export default function PurchaseInvoicesIndex({
    purchaseInvoices,
    pagination,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [supplierInvoiceNumber, setSupplierInvoiceNumber] = useState(
        filters.supplierInvoiceNumber ?? '',
    );
    const [dateFrom, setDateFrom] = useState(filters.dateFrom ?? '');
    const [dateTo, setDateTo] = useState(filters.dateTo ?? '');

    function applyFilters(event: React.FormEvent) {
        event.preventDefault();

        // Empty values are dropped rather than sent: the ERP narrows a result
        // set to rows matching an empty filter.
        const query = Object.fromEntries(
            Object.entries({
                search,
                supplierInvoiceNumber,
                dateFrom,
                dateTo,
            }).filter(([, value]) => value !== ''),
        );

        router.get(purchaseInvoicesIndex().url, query, {
            preserveState: true,
            replace: true,
        });
    }

    function goToPage(page: number) {
        router.get(
            purchaseInvoicesIndex().url,
            { ...filters, page },
            { preserveState: true, preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Purchase invoices" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Purchase invoices"
                    description="Supplier invoices, read live from the ERP."
                />

                <form
                    onSubmit={applyFilters}
                    className="flex flex-wrap items-end gap-3"
                >
                    <div className="grid w-full gap-1.5 sm:w-64">
                        <Label htmlFor="pi-search">Search</Label>
                        <Input
                            id="pi-search"
                            value={search}
                            onChange={(event) => {
                                setSearch(event.target.value);
                            }}
                            placeholder="Number, supplier, notes…"
                        />
                    </div>
                    <div className="grid w-full gap-1.5 sm:w-48">
                        <Label htmlFor="pi-supplier-invoice">
                            Supplier invoice no.
                        </Label>
                        <Input
                            id="pi-supplier-invoice"
                            value={supplierInvoiceNumber}
                            maxLength={50}
                            onChange={(event) => {
                                setSupplierInvoiceNumber(event.target.value);
                            }}
                        />
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="pi-date-from">Invoice date from</Label>
                        <Input
                            id="pi-date-from"
                            type="date"
                            value={dateFrom}
                            onChange={(event) => {
                                setDateFrom(event.target.value);
                            }}
                        />
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="pi-date-to">to</Label>
                        <Input
                            id="pi-date-to"
                            type="date"
                            value={dateTo}
                            min={dateFrom || undefined}
                            onChange={(event) => {
                                setDateTo(event.target.value);
                            }}
                        />
                    </div>
                    <Button type="submit" variant="secondary">
                        Apply
                    </Button>
                </form>

                <div className="border-sidebar-border/70 dark:border-sidebar-border overflow-x-auto rounded-xl border p-2">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Number</TableHead>
                                <TableHead>Supplier</TableHead>
                                <TableHead>Supplier invoice</TableHead>
                                <TableHead>Date</TableHead>
                                <TableHead>Due</TableHead>
                                <TableHead>Branch</TableHead>
                                <TableHead className="text-right">
                                    Total
                                </TableHead>
                                <TableHead>Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {purchaseInvoices.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={8}
                                        className="text-muted-foreground py-8 text-center"
                                    >
                                        No purchase invoices matched this
                                        filter.
                                    </TableCell>
                                </TableRow>
                            )}

                            {purchaseInvoices.map((invoice, index) => (
                                <TableRow
                                    key={
                                        invoice.number !== ''
                                            ? invoice.number
                                            : `row-${index}`
                                    }
                                >
                                    <TableCell className="font-mono text-xs">
                                        {invoice.number !== '' ? (
                                            <Link
                                                href={purchaseInvoicesShow(
                                                    invoice.number,
                                                )}
                                                className="underline-offset-4 hover:underline"
                                            >
                                                {invoice.number}
                                            </Link>
                                        ) : (
                                            '—'
                                        )}
                                    </TableCell>
                                    <TableCell className="font-medium">
                                        {invoice.supplierName ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground font-mono text-xs">
                                        {invoice.supplierInvoiceNumber ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        {formatDate(invoice.date)}
                                    </TableCell>
                                    <TableCell>
                                        {formatDate(invoice.dueDate)}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {invoice.branchName ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {formatAmount(
                                            invoice.total,
                                            invoice.currencySign,
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {invoice.statusName ? (
                                            <Badge variant="secondary">
                                                {invoice.statusName}
                                            </Badge>
                                        ) : (
                                            '—'
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {pagination && (
                    <div className="flex items-center justify-between gap-4">
                        <p className="text-muted-foreground text-sm">
                            Page {pagination.page} of {pagination.lastPage} —{' '}
                            {pagination.count} purchase invoices
                        </p>
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={pagination.page <= 1}
                                onClick={() => {
                                    goToPage(pagination.page - 1);
                                }}
                            >
                                Previous
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={!pagination.hasMorePages}
                                onClick={() => {
                                    goToPage(pagination.page + 1);
                                }}
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

PurchaseInvoicesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Purchase invoices',
            href: purchaseInvoicesIndex(),
        },
    ],
};
