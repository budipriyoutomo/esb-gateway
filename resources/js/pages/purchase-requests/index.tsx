import { Head, Link, router } from '@inertiajs/react';
import { FileUp, Plus } from 'lucide-react';
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
import { usePermissions } from '@/hooks/use-permissions';
import {
    create as purchaseRequestsCreate,
    index as purchaseRequestsIndex,
    show as purchaseRequestsShow,
} from '@/routes/purchase-requests';
import { create as purchaseRequestsImport } from '@/routes/purchase-requests/import';
import type { DocumentFilters, Pagination } from '@/types/pagination';
import { formatDate } from '@/lib/format';

type PurchaseRequestSummary = {
    provider: string;
    number: string;
    // Dates are the strings the ERP sent (format not documented). formatDate()
    // formats the shapes it recognises and shows anything else as-is.
    date: string | null;
    requiredDate: string | null;
    createdAt: string | null;
    branchId: number | null;
    branchName: string | null;
    templateId: number | null;
    templateName: string | null;
    statusId: number | null;
    statusName: string | null;
    notes: string | null;
};

type Filters = DocumentFilters & {
    [key: string]: string | number | number[] | undefined;
};

type Props = {
    purchaseRequests: PurchaseRequestSummary[];
    pagination: Pagination | null;
    filters: Filters;
};

export default function PurchaseRequestsIndex({
    purchaseRequests,
    pagination,
    filters,
}: Props) {
    const { can } = usePermissions();
    const canManage = can('esb.invoke') && can('purchase-requests.manage');
    const [search, setSearch] = useState(filters.search ?? '');
    const [dateFrom, setDateFrom] = useState(filters.dateFrom ?? '');
    const [dateTo, setDateTo] = useState(filters.dateTo ?? '');

    function applyFilters(event: React.FormEvent) {
        event.preventDefault();

        // Empty values are dropped rather than sent: the ERP narrows a result
        // set to rows matching an empty filter.
        const query = Object.fromEntries(
            Object.entries({ search, dateFrom, dateTo }).filter(
                ([, value]) => value !== '',
            ),
        );

        router.get(purchaseRequestsIndex().url, query, {
            preserveState: true,
            replace: true,
        });
    }

    function goToPage(page: number) {
        router.get(
            purchaseRequestsIndex().url,
            { ...filters, page },
            { preserveState: true, preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Purchase requests" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <Heading
                        title="Purchase requests"
                        description="Purchase requests, read live from the ERP."
                    />
                    {canManage && (
                        <div className="flex gap-2">
                            <Button variant="outline" size="sm" asChild>
                                <Link href={purchaseRequestsImport()}>
                                    <FileUp className="size-4" />
                                    Import
                                </Link>
                            </Button>
                            <Button size="sm" asChild>
                                <Link href={purchaseRequestsCreate()}>
                                    <Plus className="size-4" />
                                    New request
                                </Link>
                            </Button>
                        </div>
                    )}
                </div>

                <form
                    onSubmit={applyFilters}
                    className="flex flex-wrap items-end gap-3"
                >
                    <div className="grid w-full gap-1.5 sm:w-64">
                        <Label htmlFor="pr-search">Search</Label>
                        <Input
                            id="pr-search"
                            value={search}
                            onChange={(event) => {
                                setSearch(event.target.value);
                            }}
                            placeholder="Number, branch, notes…"
                        />
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="pr-date-from">Request date from</Label>
                        <Input
                            id="pr-date-from"
                            type="date"
                            value={dateFrom}
                            onChange={(event) => {
                                setDateFrom(event.target.value);
                            }}
                        />
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="pr-date-to">to</Label>
                        <Input
                            id="pr-date-to"
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
                                <TableHead>Request date</TableHead>
                                <TableHead>Required</TableHead>
                                <TableHead>Branch</TableHead>
                                <TableHead>Template</TableHead>
                                <TableHead>Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {purchaseRequests.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="text-muted-foreground py-8 text-center"
                                    >
                                        No purchase requests matched this
                                        filter.
                                    </TableCell>
                                </TableRow>
                            )}

                            {purchaseRequests.map((request) => (
                                <TableRow key={request.number}>
                                    <TableCell className="font-mono text-xs">
                                        {request.number !== '' ? (
                                            <Link
                                                href={purchaseRequestsShow(
                                                    request.number,
                                                )}
                                                className="underline-offset-4 hover:underline"
                                            >
                                                {request.number}
                                            </Link>
                                        ) : (
                                            '—'
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {formatDate(request.date)}
                                    </TableCell>
                                    <TableCell>
                                        {formatDate(request.requiredDate)}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {request.branchName ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {request.templateName ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        {request.statusName ? (
                                            <Badge variant="secondary">
                                                {request.statusName}
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
                            {pagination.count} purchase requests
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

PurchaseRequestsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Purchase requests',
            href: purchaseRequestsIndex(),
        },
    ],
};
