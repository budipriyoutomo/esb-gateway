import { Head, Link } from '@inertiajs/react';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { FilterBar } from '@/components/filter-bar';
import { ListPagination } from '@/components/list-pagination';
import { ClosingReadinessBadge } from '@/components/closing-readiness-badge';
import { PageHeader } from '@/components/page-header';
import { SelectFilterField } from '@/components/select-filter-field';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFilterForm } from '@/hooks/use-filter-form';
import { formatDate, formatDateTime } from '@/lib/format';
import { fromPaginator } from '@/lib/pagination';
import type { LaravelPaginator } from '@/lib/pagination';
import { outlets as mappingsOutlets } from '@/routes/closing-mappings';
import {
    index as closingRecapsIndex,
    show as showClosingRecap,
} from '@/routes/closing-recaps';
import type { ClosingRecapStatus } from '@/types/closing';

type ClosingRecapRow = {
    id: string;
    outletCode: string;
    productionDate: string;
    sequence: number;
    items: number;
    status: ClosingRecapStatus;
    lastReceivedAt: string | null;
    ready: boolean;
    missing: number;
};

type Props = {
    recaps: LaravelPaginator<ClosingRecapRow>;
    filters: Record<string, string>;
    statuses: ClosingRecapStatus[];
};

const columns: DataTableColumn<ClosingRecapRow>[] = [
    {
        key: 'productionDate',
        header: 'Production date',
        className: 'font-medium',
        cell: (recap) => (
            <Link
                href={showClosingRecap(recap.id)}
                className="underline-offset-4 hover:underline"
            >
                {formatDate(recap.productionDate)}
            </Link>
        ),
    },
    { key: 'outlet', header: 'Outlet', cell: (recap) => recap.outletCode },
    {
        key: 'sequence',
        header: 'Sequence',
        align: 'right',
        cell: (recap) => recap.sequence,
    },
    {
        key: 'items',
        header: 'Items',
        align: 'right',
        cell: (recap) => recap.items,
    },
    {
        key: 'status',
        header: 'Status',
        cell: (recap) => <Badge variant="secondary">{recap.status}</Badge>,
    },
    {
        key: 'mappings',
        header: 'Mappings',
        cell: (recap) => (
            <ClosingReadinessBadge
                ready={recap.ready}
                missing={recap.missing}
            />
        ),
    },
    {
        key: 'received',
        header: 'Last received',
        className: 'text-muted-foreground text-xs',
        cell: (recap) => formatDateTime(recap.lastReceivedAt),
    },
];

export default function ClosingRecapsIndex({
    recaps,
    filters,
    statuses,
}: Props) {
    const form = useFilterForm(closingRecapsIndex().url, filters, [
        { key: 'outletCode', chip: (value) => `Outlet ${value}` },
        { key: 'productionDate', chip: (value) => formatDate(value) },
        { key: 'status', chip: (value) => `Status ${value}` },
    ]);

    return (
        <>
            <Head title="Closing recaps" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Closing recaps"
                    description="Daily closing recaps sync-api Maharasa sent, held in the gateway until an operator sends them to the ERP."
                    actions={
                        <Button variant="outline" size="sm" asChild>
                            <Link href={mappingsOutlets()}>Mappings</Link>
                        </Button>
                    }
                />

                <FilterBar
                    id="closing-recaps"
                    search={{
                        label: 'Outlet code',
                        value: form.values.outletCode!,
                        onChange: (value) => form.set('outletCode', value),
                        placeholder: 'Outlet code',
                    }}
                    {...form.bar}
                    defaultAdvancedOpen={Boolean(
                        filters.productionDate || filters.status,
                    )}
                >
                    <div className="grid w-full gap-1.5 sm:w-44">
                        <Label htmlFor="closing-recaps-date">
                            Production date
                        </Label>
                        <Input
                            id="closing-recaps-date"
                            type="date"
                            value={form.values.productionDate}
                            onChange={(event) => {
                                form.set('productionDate', event.target.value);
                            }}
                        />
                    </div>
                    <SelectFilterField
                        id="closing-recaps-status"
                        label="Status"
                        anyLabel="Any status"
                        options={statuses.map((status) => ({
                            value: status,
                            label: status,
                        }))}
                        value={form.values.status!}
                        onChange={(status) => {
                            form.set('status', status);
                            form.apply({ ...form.values, status });
                        }}
                    />
                </FilterBar>

                <DataTable
                    caption="Closing recaps"
                    columns={columns}
                    rows={recaps.data}
                    rowKey={(recap) => recap.id}
                    rowHref={(recap) => showClosingRecap(recap.id).url}
                    empty="No closing recaps matched these filters."
                />

                <ListPagination
                    pagination={fromPaginator(recaps)}
                    noun="recaps"
                    onPageChange={form.goToPage}
                />
            </div>
        </>
    );
}
