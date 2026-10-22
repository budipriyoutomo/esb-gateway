import { Head, Link } from '@inertiajs/react';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { FilterBar } from '@/components/filter-bar';
import { ListPagination } from '@/components/list-pagination';
import { PageHeader } from '@/components/page-header';
import { SelectFilterField } from '@/components/select-filter-field';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFilterForm } from '@/hooks/use-filter-form';
import { formatDateTime } from '@/lib/format';
import { fromPaginator } from '@/lib/pagination';
import type { LaravelPaginator } from '@/lib/pagination';
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

type Props = {
    pushes: LaravelPaginator<SalesPushRow>;
    filters: Record<string, string>;
    statuses: SalesPushStatus[];
};

/**
 * The gateway's own push states, not ERP statuses, so they keep their own
 * colours rather than StatusBadge's keyword guess.
 */
export function statusVariant(
    status: SalesPushStatus,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'succeeded') return 'default';
    if (status === 'queued') return 'secondary';
    if (status === 'superseded') return 'outline';

    return 'destructive';
}

const columns: DataTableColumn<SalesPushRow>[] = [
    {
        key: 'received',
        header: 'Received',
        className: 'text-muted-foreground text-xs',
        cell: (push) => formatDateTime(push.createdAt),
    },
    {
        key: 'salesNum',
        header: 'Sales number',
        className: 'font-medium',
        cell: (push) => (
            <Link
                href={showSalesPush(push.id)}
                className="underline-offset-4 hover:underline"
            >
                {push.salesNum}
            </Link>
        ),
    },
    { key: 'branch', header: 'Branch', cell: (push) => push.branchCode },
    {
        key: 'salesDate',
        header: 'Sales date',
        cell: (push) => push.salesDate ?? '—',
    },
    {
        key: 'client',
        header: 'Client',
        className: 'text-muted-foreground text-xs',
        cell: (push) => push.client ?? '—',
    },
    {
        key: 'status',
        header: 'Status',
        cell: (push) => (
            <Badge variant={statusVariant(push.status)}>{push.status}</Badge>
        ),
    },
    {
        key: 'attempts',
        header: 'Attempts',
        align: 'right',
        className: 'text-muted-foreground text-xs',
        cell: (push) => push.attempts,
    },
];

export default function SalesPushesIndex({ pushes, filters, statuses }: Props) {
    const form = useFilterForm(salesPushesIndex().url, filters, [
        { key: 'salesNum', chip: (value) => `“${value}”` },
        { key: 'branchCode', chip: (value) => `Branch ${value}` },
        { key: 'status', chip: (value) => `Status ${value}` },
    ]);

    return (
        <>
            <Head title="Sales pushes" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Sales pushes"
                    description="Sales transactions POS clients sent through the gateway, and what the ERP said about each."
                />

                <FilterBar
                    id="pushes"
                    search={{
                        label: 'Sales number',
                        value: form.values.salesNum!,
                        onChange: (value) => form.set('salesNum', value),
                        placeholder: 'Sales number',
                    }}
                    {...form.bar}
                    defaultAdvancedOpen={Boolean(
                        filters.branchCode || filters.status,
                    )}
                >
                    <div className="grid w-full gap-1.5 sm:w-40">
                        <Label htmlFor="pushes-branch">Branch code</Label>
                        <Input
                            id="pushes-branch"
                            value={form.values.branchCode}
                            onChange={(event) => {
                                form.set('branchCode', event.target.value);
                            }}
                        />
                    </div>
                    <SelectFilterField
                        id="pushes-status"
                        label="Status"
                        anyLabel="Any status"
                        options={statuses.map((status) => ({
                            value: status,
                            label: status,
                        }))}
                        value={form.values.status!}
                        onChange={(status) => {
                            // A status applies at once, as it did before.
                            form.set('status', status);
                            form.apply({ ...form.values, status });
                        }}
                    />
                </FilterBar>

                <DataTable
                    caption="Sales pushes"
                    columns={columns}
                    rows={pushes.data}
                    rowKey={(push) => push.id}
                    rowHref={(push) => showSalesPush(push.id).url}
                    empty="No sales pushes matched these filters."
                />

                <ListPagination
                    pagination={fromPaginator(pushes)}
                    noun="pushes"
                    onPageChange={form.goToPage}
                />
            </div>
        </>
    );
}
