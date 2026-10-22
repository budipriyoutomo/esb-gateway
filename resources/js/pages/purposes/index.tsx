import { Head, Link } from '@inertiajs/react';
import { ActiveStatusField } from '@/components/active-status-field';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { FilterBar } from '@/components/filter-bar';
import { ListPagination } from '@/components/list-pagination';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFilterForm } from '@/hooks/use-filter-form';
import {
    index as purposesIndex,
    show as purposesShow,
} from '@/routes/purposes';
import type { Pagination, PagingFilters } from '@/types/pagination';
import type { PurposeSummary } from '@/types/purpose';

type Filters = PagingFilters & {
    purposeName?: string;
    purposeCoaNo?: string;
    flagActive?: number;
};

type Props = {
    purposes: PurposeSummary[];
    pagination: Pagination | null;
    filters: Filters;
};

const columns: DataTableColumn<PurposeSummary>[] = [
    {
        key: 'name',
        header: 'Name',
        className: 'font-medium',
        cell: (purpose) =>
            purpose.id !== null ? (
                <Link
                    href={purposesShow(purpose.id)}
                    className="underline-offset-4 hover:underline"
                >
                    {purpose.name || `#${String(purpose.id)}`}
                </Link>
            ) : (
                purpose.name || '—'
            ),
    },
    {
        key: 'account',
        header: 'Account',
        cell: (purpose) => (
            <span>
                <span className="font-mono text-xs">
                    {purpose.accountNo ?? '—'}
                </span>
                {purpose.accountName && (
                    <span className="text-muted-foreground block text-xs">
                        {purpose.accountName}
                    </span>
                )}
            </span>
        ),
    },
    {
        key: 'appliesTo',
        header: 'Applies to',
        cell: (purpose) =>
            purpose.appliesTo.length === 0 ? (
                '—'
            ) : (
                <span className="flex flex-wrap gap-1">
                    {purpose.appliesTo.map((type) => (
                        <Badge key={type} variant="outline">
                            {type}
                        </Badge>
                    ))}
                </span>
            ),
    },
    {
        key: 'status',
        header: 'Status',
        cell: (purpose) => (
            <StatusBadge
                status={
                    purpose.active === null
                        ? null
                        : purpose.active
                          ? 'Active'
                          : 'Inactive'
                }
            />
        ),
    },
];

export default function PurposesIndex({
    purposes,
    pagination,
    filters,
}: Props) {
    const form = useFilterForm(purposesIndex().url, filters, [
        { key: 'purposeName', chip: (value) => `“${value}”` },
        { key: 'purposeCoaNo', chip: (value) => `Account ${value}` },
        {
            key: 'flagActive',
            chip: (value) => (value === '1' ? 'Active' : 'Inactive'),
        },
    ]);

    return (
        <>
            <Head title="Purposes" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Purposes"
                    description="Transaction purposes and the accounts they post to, from ESB Core."
                />

                <FilterBar
                    id="purposes"
                    search={{
                        label: 'Purpose name',
                        value: form.values.purposeName!,
                        onChange: (value) => form.set('purposeName', value),
                        placeholder: 'Purpose name',
                    }}
                    {...form.bar}
                    defaultAdvancedOpen={
                        Boolean(filters.purposeCoaNo) ||
                        filters.flagActive !== undefined
                    }
                >
                    <div className="grid w-full gap-1.5 sm:w-40">
                        <Label htmlFor="purposes-account">Account number</Label>
                        <Input
                            id="purposes-account"
                            value={form.values.purposeCoaNo}
                            onChange={(event) => {
                                form.set('purposeCoaNo', event.target.value);
                            }}
                        />
                    </div>
                    <ActiveStatusField
                        id="purposes-status"
                        value={form.values.flagActive!}
                        onChange={(flagActive) => {
                            // A status applies at once, as it did before.
                            form.set('flagActive', flagActive);
                            form.apply({ ...form.values, flagActive });
                        }}
                    />
                </FilterBar>

                <DataTable
                    caption="Purposes"
                    columns={columns}
                    rows={purposes}
                    rowKey={(purpose, index) =>
                        `${String(purpose.id)}-${String(index)}`
                    }
                    rowHref={(purpose) =>
                        purpose.id !== null
                            ? purposesShow(purpose.id).url
                            : null
                    }
                    empty="No purposes matched."
                />

                <ListPagination
                    pagination={pagination}
                    noun="purposes"
                    onPageChange={form.goToPage}
                />
            </div>
        </>
    );
}
