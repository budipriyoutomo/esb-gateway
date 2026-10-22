import { Head, Link } from '@inertiajs/react';
import { FileUp, Plus } from 'lucide-react';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { DateRangeField } from '@/components/date-range-field';
import { FilterBar } from '@/components/filter-bar';
import { ListPagination } from '@/components/list-pagination';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { useFilterForm } from '@/hooks/use-filter-form';
import { usePermissions } from '@/hooks/use-permissions';
import { formatDate } from '@/lib/format';
import {
    create as purchaseRequestsCreate,
    index as purchaseRequestsIndex,
    show as purchaseRequestsShow,
} from '@/routes/purchase-requests';
import { create as purchaseRequestsImport } from '@/routes/purchase-requests/import';
import type { DocumentFilters, Pagination } from '@/types/pagination';

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

const columns: DataTableColumn<PurchaseRequestSummary>[] = [
    {
        key: 'number',
        header: 'Number',
        className: 'font-mono text-xs',
        cell: (request) =>
            request.number !== '' ? (
                <Link
                    href={purchaseRequestsShow(request.number)}
                    className="underline-offset-4 hover:underline"
                >
                    {request.number}
                </Link>
            ) : (
                '—'
            ),
    },
    {
        key: 'date',
        header: 'Request date',
        cell: (request) => formatDate(request.date),
    },
    {
        key: 'required',
        header: 'Required',
        cell: (request) => formatDate(request.requiredDate),
    },
    {
        key: 'branch',
        header: 'Branch',
        className: 'text-muted-foreground',
        cell: (request) => request.branchName ?? '—',
    },
    {
        key: 'template',
        header: 'Template',
        className: 'text-muted-foreground',
        cell: (request) => request.templateName ?? '—',
    },
    {
        key: 'status',
        header: 'Status',
        cell: (request) => <StatusBadge status={request.statusName} />,
    },
];

export default function PurchaseRequestsIndex({
    purchaseRequests,
    pagination,
    filters,
}: Props) {
    const { can } = usePermissions();
    const canManage = can('esb.invoke') && can('purchase-requests.manage');
    const form = useFilterForm(purchaseRequestsIndex().url, filters, [
        { key: 'search', chip: (value) => `“${value}”` },
        { key: 'dateFrom', chip: (value) => `From ${formatDate(value)}` },
        { key: 'dateTo', chip: (value) => `To ${formatDate(value)}` },
    ]);

    return (
        <>
            <Head title="Purchase requests" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Purchase requests"
                    description="Purchase requests, read live from the ERP."
                    actions={
                        canManage && (
                            <>
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
                            </>
                        )
                    }
                />

                <FilterBar
                    id="pr"
                    search={{
                        value: form.values.search!,
                        onChange: (value) => form.set('search', value),
                        placeholder: 'Number, branch, notes…',
                    }}
                    {...form.bar}
                    defaultAdvancedOpen={Boolean(
                        filters.dateFrom || filters.dateTo,
                    )}
                >
                    <DateRangeField
                        id="pr-date"
                        label="Request date"
                        from={form.values.dateFrom!}
                        to={form.values.dateTo!}
                        onChange={({ from, to }) => {
                            form.set('dateFrom', from);
                            form.set('dateTo', to);
                        }}
                    />
                </FilterBar>

                <DataTable
                    caption="Purchase requests"
                    columns={columns}
                    rows={purchaseRequests}
                    rowKey={(request, index) =>
                        request.number !== '' ? request.number : `row-${index}`
                    }
                    rowHref={(request) =>
                        request.number !== ''
                            ? purchaseRequestsShow(request.number).url
                            : null
                    }
                    empty="No purchase requests matched this filter. Try a wider date range or reset the filters."
                />

                <ListPagination
                    pagination={pagination}
                    noun="purchase requests"
                    onPageChange={form.goToPage}
                />
            </div>
        </>
    );
}
