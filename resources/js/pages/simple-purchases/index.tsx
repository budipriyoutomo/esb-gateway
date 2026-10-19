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
    index as simplePurchasesIndex,
    show as simplePurchasesShow,
} from '@/routes/simple-purchases';
import type { SimplePurchaseSummary } from '@/types/simple-purchase';
import type { DocumentFilters, Pagination } from '@/types/pagination';
import { formatAmount, formatDate } from '@/lib/format';

type Filters = DocumentFilters & {
    supplierInvoiceNumber?: string;
    [key: string]: string | number | undefined;
};

type Props = {
    simplePurchases: SimplePurchaseSummary[];
    pagination: Pagination | null;
    filters: Filters;
};

export default function SimplePurchasesIndex({
    simplePurchases,
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

        router.get(simplePurchasesIndex().url, query, {
            preserveState: true,
            replace: true,
        });
    }

    function goToPage(page: number) {
        router.get(
            simplePurchasesIndex().url,
            { ...filters, page },
            { preserveState: true, preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Simple purchases" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Simple purchases"
                    description="Purchases bought and paid for in one step, read live from the ERP."
                />

                <form
                    onSubmit={applyFilters}
                    className="flex flex-wrap items-end gap-3"
                >
                    <div className="grid w-full gap-1.5 sm:w-64">
                        <Label htmlFor="sp-search">Search</Label>
                        <Input
                            id="sp-search"
                            value={search}
                            onChange={(event) => {
                                setSearch(event.target.value);
                            }}
                            placeholder="Number, supplier, notes…"
                        />
                    </div>
                    <div className="grid w-full gap-1.5 sm:w-48">
                        <Label htmlFor="sp-supplier-invoice">
                            Supplier invoice no.
                        </Label>
                        <Input
                            id="sp-supplier-invoice"
                            value={supplierInvoiceNumber}
                            maxLength={50}
                            onChange={(event) => {
                                setSupplierInvoiceNumber(event.target.value);
                            }}
                        />
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="sp-date-from">Date from</Label>
                        <Input
                            id="sp-date-from"
                            type="date"
                            value={dateFrom}
                            onChange={(event) => {
                                setDateFrom(event.target.value);
                            }}
                        />
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="sp-date-to">to</Label>
                        <Input
                            id="sp-date-to"
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
                                <TableHead>Branch</TableHead>
                                <TableHead className="text-right">
                                    Total
                                </TableHead>
                                <TableHead>Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {simplePurchases.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="text-muted-foreground py-8 text-center"
                                    >
                                        No simple purchases matched this filter.
                                    </TableCell>
                                </TableRow>
                            )}

                            {simplePurchases.map((purchase, index) => (
                                <TableRow
                                    key={
                                        purchase.number !== ''
                                            ? purchase.number
                                            : `row-${index}`
                                    }
                                >
                                    <TableCell className="font-mono text-xs">
                                        {purchase.number !== '' ? (
                                            <Link
                                                href={simplePurchasesShow(
                                                    purchase.number,
                                                )}
                                                className="underline-offset-4 hover:underline"
                                            >
                                                {purchase.number}
                                            </Link>
                                        ) : (
                                            '—'
                                        )}
                                    </TableCell>
                                    <TableCell className="font-medium">
                                        {purchase.supplierName ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground font-mono text-xs">
                                        {purchase.supplierInvoiceNumber ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        {formatDate(purchase.date)}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {purchase.branchName ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {formatAmount(
                                            purchase.total,
                                            purchase.currencySign,
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {purchase.statusName ? (
                                            <Badge variant="secondary">
                                                {purchase.statusName}
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
                            {pagination.count} simple purchases
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

SimplePurchasesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Simple purchases',
            href: simplePurchasesIndex(),
        },
    ],
};
