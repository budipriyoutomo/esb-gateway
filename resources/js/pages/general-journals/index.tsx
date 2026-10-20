import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { DateRangeField } from '@/components/date-range-field';
import { FilterBar } from '@/components/filter-bar';
import type { ActiveFilter } from '@/components/filter-bar';
import { ListPagination } from '@/components/list-pagination';
import { PageHeader } from '@/components/page-header';
import { useListQuery } from '@/hooks/use-list-query';
import { formatDate } from '@/lib/format';
import {
    index as indexRoute,
    show as showRoute,
} from '@/routes/general-journals';
import type { GeneralJournalEntry } from '@/types/accounting';
import type { Pagination } from '@/types/pagination';

type Filters = {
    page?: number;
    limit?: number;
    dateFrom?: string;
    dateTo?: string;
    search?: string;
    [key: string]: string | number | undefined;
};

type Props = {
    entries: GeneralJournalEntry[];
    pagination: Pagination | null;
    filters: Filters;
};

const columns: DataTableColumn<GeneralJournalEntry>[] = [
    {
        key: 'date',
        header: 'Date',
        cell: (entry) => (
            <Link
                href={showRoute(entry.journalHeadId).url}
                className="underline-offset-4 hover:underline"
            >
                {formatDate(entry.date)}
            </Link>
        ),
    },
    {
        key: 'type',
        header: 'Transaction',
        className: 'font-medium',
        cell: (entry) => (
            <>
                {entry.transactionType ?? '—'}
                {entry.viewableInErp === false && (
                    <span className="text-muted-foreground block text-xs font-normal">
                        Not viewable in the ERP
                    </span>
                )}
            </>
        ),
    },
    {
        key: 'reference',
        header: 'Reference',
        className: 'font-mono text-xs',
        cell: (entry) => entry.reference ?? '—',
    },
    {
        key: 'notes',
        header: 'Notes',
        className: 'text-muted-foreground',
        cell: (entry) => entry.notes ?? '—',
    },
];

/**
 * The general journal ([ESB-041]): every journal the ERP's modules posted,
 * searched by text and date. Filters by branch, account, cost center and
 * project wait for the ERP to document its list parameters.
 */
export default function GeneralJournalIndex({
    entries,
    pagination,
    filters,
}: Props) {
    const list = useListQuery(indexRoute().url, filters);
    const [search, setSearch] = useState(filters.search ?? '');
    const [dates, setDates] = useState({
        from: filters.dateFrom ?? '',
        to: filters.dateTo ?? '',
    });

    const active: ActiveFilter[] = [
        ...(filters.search
            ? [{ key: 'search', label: `“${filters.search}”` }]
            : []),
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

    return (
        <>
            <Head title="General journal" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="General journal"
                    description="Journals posted in the ERP, read live."
                />

                <FilterBar
                    id="general-journals"
                    search={{
                        value: search,
                        onChange: setSearch,
                        placeholder: 'Account, branch, reference, notes…',
                    }}
                    onSubmit={() =>
                        list.apply({
                            search,
                            dateFrom: dates.from,
                            dateTo: dates.to,
                        })
                    }
                    active={active}
                    onRemove={(key) => {
                        if (key === 'search') {
                            setSearch('');
                        } else {
                            setDates((range) => ({
                                ...range,
                                [key === 'dateFrom' ? 'from' : 'to']: '',
                            }));
                        }

                        list.apply({
                            ...filters,
                            page: undefined,
                            [key]: undefined,
                        });
                    }}
                    onReset={() => {
                        setSearch('');
                        setDates({ from: '', to: '' });
                        list.reset();
                    }}
                    defaultAdvancedOpen={Boolean(
                        filters.dateFrom || filters.dateTo,
                    )}
                >
                    <DateRangeField
                        id="general-journals-date"
                        label="Date"
                        from={dates.from}
                        to={dates.to}
                        onChange={setDates}
                    />
                </FilterBar>

                <DataTable
                    caption="General journal"
                    columns={columns}
                    rows={entries}
                    rowKey={(entry) => String(entry.journalHeadId)}
                    rowHref={(entry) => showRoute(entry.journalHeadId).url}
                    empty="No journals matched this filter. Try a wider date range or reset the filters."
                />

                <ListPagination
                    pagination={pagination}
                    noun="journals"
                    onPageChange={list.goToPage}
                />
            </div>
        </>
    );
}
