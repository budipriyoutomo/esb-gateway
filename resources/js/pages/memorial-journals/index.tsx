import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { DateRangeField } from '@/components/date-range-field';
import { FilterBar } from '@/components/filter-bar';
import type { ActiveFilter } from '@/components/filter-bar';
import { ListPagination } from '@/components/list-pagination';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Label } from '@/components/ui/label';
import { useListQuery } from '@/hooks/use-list-query';
import { formatDate } from '@/lib/format';
import {
    index as indexRoute,
    show as showRoute,
} from '@/routes/memorial-journals';
import type { MemorialJournalSummary } from '@/types/accounting';
import type { Pagination } from '@/types/pagination';

type Filters = {
    page?: number;
    limit?: number;
    number?: string;
    dateFrom?: string;
    dateTo?: string;
    statusId?: number;
    [key: string]: string | number | undefined;
};

type Props = {
    journals: MemorialJournalSummary[];
    pagination: Pagination | null;
    filters: Filters;
    statuses: { id: number; name: string }[];
};

const selectClass =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px]';

const columns: DataTableColumn<MemorialJournalSummary>[] = [
    {
        key: 'number',
        header: 'Number',
        className: 'font-mono text-xs',
        cell: (journal) => (
            <Link
                href={showRoute(journal.number).url}
                className="underline-offset-4 hover:underline"
            >
                {journal.number}
            </Link>
        ),
    },
    {
        key: 'date',
        header: 'Date',
        cell: (journal) => formatDate(journal.date),
    },
    {
        key: 'notes',
        header: 'Notes',
        cell: (journal) => journal.notes ?? '—',
    },
    {
        key: 'createdBy',
        header: 'Created by',
        className: 'text-muted-foreground',
        cell: (journal) => journal.createdBy ?? '—',
    },
    {
        key: 'status',
        header: 'Status',
        cell: (journal) => <StatusBadge status={journal.statusName} />,
    },
];

/**
 * Memorial journals ([ESB-043]): manual journal entries, searched by number,
 * status and date.
 */
export default function MemorialJournalIndex({
    journals,
    pagination,
    filters,
    statuses,
}: Props) {
    const list = useListQuery(indexRoute().url, filters);
    const [search, setSearch] = useState(filters.number ?? '');
    const [status, setStatus] = useState(
        filters.statusId === undefined ? '' : String(filters.statusId),
    );
    const [dates, setDates] = useState({
        from: filters.dateFrom ?? '',
        to: filters.dateTo ?? '',
    });

    const statusName = (id: number) =>
        statuses.find((option) => option.id === id)?.name ?? `#${id}`;

    const active: ActiveFilter[] = [
        ...(filters.number
            ? [{ key: 'number', label: `“${filters.number}”` }]
            : []),
        ...(filters.statusId === undefined
            ? []
            : [{ key: 'statusId', label: statusName(filters.statusId) }]),
        ...(filters.dateFrom
            ? [
                  {
                      key: 'dateFrom',
                      label: `From ${formatDate(filters.dateFrom)}`,
                  },
              ]
            : []),
        ...(filters.dateTo
            ? [{ key: 'dateTo', label: `To ${formatDate(filters.dateTo)}` }]
            : []),
    ];

    function clearField(key: string) {
        if (key === 'number') {
            setSearch('');
        } else if (key === 'statusId') {
            setStatus('');
        } else {
            setDates((range) => ({
                ...range,
                [key === 'dateFrom' ? 'from' : 'to']: '',
            }));
        }
    }

    return (
        <>
            <Head title="Memorial journals" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Memorial journals"
                    description="Manual journal entries, read live from the ERP."
                />

                <FilterBar
                    id="memorial-journals"
                    search={{
                        value: search,
                        onChange: setSearch,
                        placeholder: 'Journal number',
                    }}
                    onSubmit={() =>
                        list.apply({
                            number: search,
                            statusId:
                                status === '' ? undefined : Number(status),
                            dateFrom: dates.from,
                            dateTo: dates.to,
                        })
                    }
                    active={active}
                    onRemove={(key) => {
                        clearField(key);
                        list.apply({
                            ...filters,
                            page: undefined,
                            [key]: undefined,
                        });
                    }}
                    onReset={() => {
                        setSearch('');
                        setStatus('');
                        setDates({ from: '', to: '' });
                        list.reset();
                    }}
                    defaultAdvancedOpen={
                        filters.statusId !== undefined ||
                        Boolean(filters.dateFrom || filters.dateTo)
                    }
                >
                    <div className="grid gap-1.5">
                        <Label htmlFor="memorial-journals-status">Status</Label>
                        <select
                            id="memorial-journals-status"
                            className={selectClass}
                            value={status}
                            onChange={(event) => setStatus(event.target.value)}
                        >
                            <option value="">All</option>
                            {statuses.map((option) => (
                                <option key={option.id} value={option.id}>
                                    {option.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <DateRangeField
                        id="memorial-journals-date"
                        label="Date"
                        from={dates.from}
                        to={dates.to}
                        onChange={setDates}
                    />
                </FilterBar>

                <DataTable
                    caption="Memorial journals"
                    columns={columns}
                    rows={journals}
                    rowKey={(journal) => journal.number}
                    rowHref={(journal) => showRoute(journal.number).url}
                    empty="No memorial journals matched this filter. Try a wider date range or reset the filters."
                />

                <ListPagination
                    pagination={pagination}
                    noun="memorial journals"
                    onPageChange={list.goToPage}
                />
            </div>
        </>
    );
}
