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
    index as purposesIndex,
    show as purposesShow,
} from '@/routes/purposes';
import type { PurposeSummary } from '@/types/purpose';

type Pagination = {
    page: number;
    limit: number;
    count: number;
    lastPage: number;
    hasMorePages: boolean;
};

type Filters = {
    page?: number;
    limit?: number;
    purposeName?: string;
    purposeCoaNo?: string;
    flagActive?: number;
};

type Props = {
    purposes: PurposeSummary[];
    pagination: Pagination | null;
    filters: Filters;
};

const ANY = 'any';

export default function PurposesIndex({
    purposes,
    pagination,
    filters,
}: Props) {
    const [purposeName, setPurposeName] = useState(filters.purposeName ?? '');
    const [purposeCoaNo, setPurposeCoaNo] = useState(
        filters.purposeCoaNo ?? '',
    );
    const [status, setStatus] = useState(
        filters.flagActive === undefined ? ANY : String(filters.flagActive),
    );

    function search(page = 1, nextStatus = status) {
        const query: Record<string, string | number> = { page };

        if (purposeName !== '') {
            query.purposeName = purposeName;
        }

        if (purposeCoaNo !== '') {
            query.purposeCoaNo = purposeCoaNo;
        }

        if (nextStatus !== ANY) {
            query.flagActive = nextStatus;
        }

        router.get(purposesIndex().url, query, {
            preserveState: true,
            replace: true,
        });
    }

    return (
        <>
            <Head title="Purposes" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Purposes"
                    description="Transaction purposes and the accounts they post to, from ESB Core."
                />

                <form
                    className="flex flex-wrap items-end gap-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        search();
                    }}
                >
                    <Input
                        value={purposeName}
                        onChange={(event) => {
                            setPurposeName(event.target.value);
                        }}
                        placeholder="Purpose name"
                        aria-label="Purpose name"
                        className="w-56"
                    />
                    <Input
                        value={purposeCoaNo}
                        onChange={(event) => {
                            setPurposeCoaNo(event.target.value);
                        }}
                        placeholder="Account no."
                        aria-label="Account number"
                        className="w-40"
                    />
                    <Select
                        value={status}
                        onValueChange={(value) => {
                            setStatus(value);
                            search(1, value);
                        }}
                    >
                        <SelectTrigger className="w-36" aria-label="Status">
                            <SelectValue placeholder="Any status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ANY}>Any status</SelectItem>
                            <SelectItem value="1">Active</SelectItem>
                            <SelectItem value="0">Inactive</SelectItem>
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
                                <TableHead>Name</TableHead>
                                <TableHead>Account</TableHead>
                                <TableHead>Applies to</TableHead>
                                <TableHead>Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {purposes.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={4}
                                        className="text-muted-foreground py-8 text-center"
                                    >
                                        No purposes matched.
                                    </TableCell>
                                </TableRow>
                            )}

                            {purposes.map((purpose, index) => (
                                <TableRow
                                    key={`${String(purpose.id)}-${String(index)}`}
                                >
                                    <TableCell className="font-medium">
                                        {purpose.id !== null ? (
                                            <Link
                                                href={purposesShow(purpose.id)}
                                                className="hover:underline"
                                            >
                                                {purpose.name ||
                                                    `#${String(purpose.id)}`}
                                            </Link>
                                        ) : (
                                            purpose.name || '—'
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <span className="font-mono text-xs">
                                            {purpose.accountNo ?? '—'}
                                        </span>
                                        {purpose.accountName && (
                                            <span className="text-muted-foreground block text-xs">
                                                {purpose.accountName}
                                            </span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex flex-wrap gap-1">
                                            {purpose.appliesTo.length === 0
                                                ? '—'
                                                : purpose.appliesTo.map(
                                                      (type) => (
                                                          <Badge
                                                              key={type}
                                                              variant="outline"
                                                          >
                                                              {type}
                                                          </Badge>
                                                      ),
                                                  )}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        {purpose.active !== null && (
                                            <Badge
                                                variant={
                                                    purpose.active
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {purpose.active
                                                    ? 'Active'
                                                    : 'Inactive'}
                                            </Badge>
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
                            {pagination.count} purposes
                        </p>
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={pagination.page <= 1}
                                onClick={() => {
                                    search(pagination.page - 1);
                                }}
                            >
                                Previous
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={!pagination.hasMorePages}
                                onClick={() => {
                                    search(pagination.page + 1);
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

PurposesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Purposes',
            href: purposesIndex(),
        },
    ],
};
