import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    index as salesPushesIndex,
    show as showSalesPush,
} from '@/routes/sales-pushes';

export type SalesPushStatus =
    | 'queued'
    | 'succeeded'
    | 'rejected'
    | 'failed'
    | 'superseded';

type SalesPushRow = {
    id: string;
    salesNum: string;
    branchCode: string;
    salesDate: string | null;
    client: string | null;
    status: SalesPushStatus;
    attempts: number;
    pushedAt: string | null;
    createdAt: string | null;
};

type Paginated = {
    data: SalesPushRow[];
    total: number;
    current_page: number;
    last_page: number;
};

type Props = {
    pushes: Paginated;
    filters: Record<string, string>;
    statuses: SalesPushStatus[];
};

const ANY = '__any__';

export function statusVariant(
    status: SalesPushStatus,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'succeeded') return 'default';
    if (status === 'queued') return 'secondary';
    if (status === 'superseded') return 'outline';

    return 'destructive';
}

export default function SalesPushesIndex({ pushes, filters, statuses }: Props) {
    const [salesNum, setSalesNum] = useState(filters.salesNum ?? '');
    const [branchCode, setBranchCode] = useState(filters.branchCode ?? '');

    function apply(overrides: Record<string, string> = {}) {
        const next: Record<string, string> = {
            ...filters,
            salesNum,
            branchCode,
            ...overrides,
        };

        for (const key of Object.keys(next)) {
            if (next[key] === '' || next[key] === ANY) {
                delete next[key];
            }
        }

        router.get(salesPushesIndex().url, next, {
            preserveState: true,
            replace: true,
        });
    }

    return (
        <>
            <Head title="Sales pushes" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Sales pushes"
                    description="Sales transactions POS clients sent through the gateway, and what the ERP said about each."
                />

                <form
                    className="flex flex-wrap items-end gap-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        apply();
                    }}
                >
                    <Input
                        value={salesNum}
                        onChange={(event) => {
                            setSalesNum(event.target.value);
                        }}
                        placeholder="Sales number"
                        aria-label="Sales number"
                        className="w-56"
                    />
                    <Input
                        value={branchCode}
                        onChange={(event) => {
                            setBranchCode(event.target.value);
                        }}
                        placeholder="Branch code"
                        aria-label="Branch code"
                        className="w-40"
                    />
                    <Select
                        value={filters.status ?? ANY}
                        onValueChange={(value) => {
                            apply({ status: value });
                        }}
                    >
                        <SelectTrigger className="w-44" aria-label="Status">
                            <SelectValue placeholder="Any status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ANY}>Any status</SelectItem>
                            {statuses.map((status) => (
                                <SelectItem key={status} value={status}>
                                    {status}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </form>

                <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-2">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Received</TableHead>
                                <TableHead>Sales number</TableHead>
                                <TableHead>Branch</TableHead>
                                <TableHead>Sales date</TableHead>
                                <TableHead>Client</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Attempts</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {pushes.data.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="text-muted-foreground py-8 text-center"
                                    >
                                        No sales pushes matched these filters.
                                    </TableCell>
                                </TableRow>
                            )}

                            {pushes.data.map((push) => (
                                <TableRow key={push.id}>
                                    <TableCell className="text-muted-foreground text-xs">
                                        {push.createdAt
                                            ? new Date(
                                                  push.createdAt,
                                              ).toLocaleString()
                                            : '—'}
                                    </TableCell>
                                    <TableCell className="font-medium">
                                        <Link
                                            href={showSalesPush(push.id)}
                                            className="hover:underline"
                                        >
                                            {push.salesNum}
                                        </Link>
                                    </TableCell>
                                    <TableCell>{push.branchCode}</TableCell>
                                    <TableCell>
                                        {push.salesDate ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground text-xs">
                                        {push.client ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={statusVariant(push.status)}
                                        >
                                            {push.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-muted-foreground text-xs">
                                        {push.attempts}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                <div className="flex items-center justify-between gap-4">
                    <p className="text-muted-foreground text-sm">
                        Page {pushes.current_page} of {pushes.last_page} —{' '}
                        {pushes.total} pushes
                    </p>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={pushes.current_page <= 1}
                            onClick={() => {
                                apply({
                                    page: String(pushes.current_page - 1),
                                });
                            }}
                        >
                            Previous
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={pushes.current_page >= pushes.last_page}
                            onClick={() => {
                                apply({
                                    page: String(pushes.current_page + 1),
                                });
                            }}
                        >
                            Next
                        </Button>
                    </div>
                </div>
            </div>
        </>
    );
}

SalesPushesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Sales pushes',
            href: salesPushesIndex(),
        },
    ],
};
