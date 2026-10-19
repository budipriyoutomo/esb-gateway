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
    index as purchaseOrdersIndex,
    show as purchaseOrdersShow,
} from '@/routes/purchase-orders';
import type { PurchaseOrderSummary } from '@/types/purchase-order';

type Pagination = {
    page: number;
    limit: number;
    count: number;
    lastPage: number;
    hasMorePages: boolean;
};

type Filters = {
    search?: string;
    dateFrom?: string;
    dateTo?: string;
    requiredDateFrom?: string;
    requiredDateTo?: string;
    [key: string]: string | number | number[] | undefined;
};

type Props = {
    purchaseOrders: PurchaseOrderSummary[];
    pagination: Pagination | null;
    filters: Filters;
};

const amount = new Intl.NumberFormat(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

// Display only: the ERP is the authority on every total.
function formatAmount(value: number | null, sign: string | null): string {
    if (value === null) {
        return '—';
    }

    return sign ? `${sign} ${amount.format(value)}` : amount.format(value);
}

export default function PurchaseOrdersIndex({
    purchaseOrders,
    pagination,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [dateFrom, setDateFrom] = useState(filters.dateFrom ?? '');
    const [dateTo, setDateTo] = useState(filters.dateTo ?? '');
    const [requiredDateFrom, setRequiredDateFrom] = useState(
        filters.requiredDateFrom ?? '',
    );
    const [requiredDateTo, setRequiredDateTo] = useState(
        filters.requiredDateTo ?? '',
    );

    function applyFilters(event: React.FormEvent) {
        event.preventDefault();

        // Empty values are dropped rather than sent: the ERP narrows a result
        // set to rows matching an empty filter.
        const query = Object.fromEntries(
            Object.entries({
                search,
                dateFrom,
                dateTo,
                requiredDateFrom,
                requiredDateTo,
            }).filter(([, value]) => value !== ''),
        );

        router.get(purchaseOrdersIndex().url, query, {
            preserveState: true,
            replace: true,
        });
    }

    function goToPage(page: number) {
        router.get(
            purchaseOrdersIndex().url,
            { ...filters, page },
            { preserveState: true, preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Purchase orders" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Purchase orders"
                    description="Orders to suppliers, read live from the ERP."
                />

                <form
                    onSubmit={applyFilters}
                    className="flex flex-wrap items-end gap-3"
                >
                    <div className="grid w-full gap-1.5 sm:w-64">
                        <Label htmlFor="po-search">Search</Label>
                        <Input
                            id="po-search"
                            value={search}
                            onChange={(event) => {
                                setSearch(event.target.value);
                            }}
                            placeholder="Number, supplier, notes…"
                        />
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="po-date-from">Order date from</Label>
                        <Input
                            id="po-date-from"
                            type="date"
                            value={dateFrom}
                            onChange={(event) => {
                                setDateFrom(event.target.value);
                            }}
                        />
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="po-date-to">to</Label>
                        <Input
                            id="po-date-to"
                            type="date"
                            value={dateTo}
                            min={dateFrom || undefined}
                            onChange={(event) => {
                                setDateTo(event.target.value);
                            }}
                        />
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="po-required-from">
                            Required date from
                        </Label>
                        <Input
                            id="po-required-from"
                            type="date"
                            value={requiredDateFrom}
                            onChange={(event) => {
                                setRequiredDateFrom(event.target.value);
                            }}
                        />
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="po-required-to">to</Label>
                        <Input
                            id="po-required-to"
                            type="date"
                            value={requiredDateTo}
                            min={requiredDateFrom || undefined}
                            onChange={(event) => {
                                setRequiredDateTo(event.target.value);
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
                                <TableHead>Date</TableHead>
                                <TableHead>Required</TableHead>
                                <TableHead>Branch</TableHead>
                                <TableHead className="text-right">
                                    Total
                                </TableHead>
                                <TableHead>Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {purchaseOrders.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="text-muted-foreground py-8 text-center"
                                    >
                                        No purchase orders matched this filter.
                                    </TableCell>
                                </TableRow>
                            )}

                            {purchaseOrders.map((order, index) => (
                                <TableRow
                                    key={
                                        order.number !== ''
                                            ? order.number
                                            : `row-${index}`
                                    }
                                >
                                    <TableCell className="font-mono text-xs">
                                        {order.number !== '' ? (
                                            <Link
                                                href={purchaseOrdersShow(
                                                    order.number,
                                                )}
                                                className="underline-offset-4 hover:underline"
                                            >
                                                {order.number}
                                            </Link>
                                        ) : (
                                            '—'
                                        )}
                                    </TableCell>
                                    <TableCell className="font-medium">
                                        {order.supplierName ?? '—'}
                                    </TableCell>
                                    <TableCell>{order.date ?? '—'}</TableCell>
                                    <TableCell>
                                        {order.requiredDate ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {order.branchName ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {formatAmount(
                                            order.total,
                                            order.currencySign,
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {order.statusName ? (
                                            <Badge variant="secondary">
                                                {order.statusName}
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
                            {pagination.count} purchase orders
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

PurchaseOrdersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Purchase orders',
            href: purchaseOrdersIndex(),
        },
    ],
};
