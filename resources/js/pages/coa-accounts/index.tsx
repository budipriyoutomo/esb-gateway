import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { DataTable } from '@/components/data-table';
import { FilterBar } from '@/components/filter-bar';
import type { ActiveFilter } from '@/components/filter-bar';
import { ListPagination } from '@/components/list-pagination';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { useListQuery } from '@/hooks/use-list-query';
import { index as coaIndex, show as coaShow } from '@/routes/coa-accounts';
import type { CoaAccountSummary } from '@/types/master-data';
import type { Pagination } from '@/types/pagination';

type Filters = { page?: number; search?: string; level?: number };

type Props = {
    accounts: CoaAccountSummary[];
    pagination: Pagination | null;
    filters: Filters;
};

const LEVELS = [1, 2, 3, 4];

const selectClass =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px]';

// Indentation per level (1 = top). Display only: the ERP sends no parent.
const INDENT: Record<number, string> = {
    1: 'pl-0 font-semibold',
    2: 'pl-4 font-medium',
    3: 'pl-8',
    4: 'pl-12',
};

export default function CoaAccountsIndex({
    accounts,
    pagination,
    filters,
}: Props) {
    const list = useListQuery(coaIndex().url, filters);
    const [search, setSearch] = useState(filters.search ?? '');
    const [level, setLevel] = useState(
        filters.level === undefined ? '' : String(filters.level),
    );

    const active: ActiveFilter[] = [
        ...(filters.search
            ? [{ key: 'search', label: `“${filters.search}”` }]
            : []),
        ...(filters.level === undefined
            ? []
            : [{ key: 'level', label: `Level ${filters.level}` }]),
    ];

    return (
        <>
            <Head title="Chart of accounts" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Chart of accounts"
                    description="Active accounts in chart order, read live from the ERP."
                />

                <FilterBar
                    id="coa"
                    search={{
                        value: search,
                        onChange: setSearch,
                        placeholder: 'Account number or description',
                    }}
                    onSubmit={() => {
                        list.apply({
                            search,
                            level: level === '' ? undefined : Number(level),
                        });
                    }}
                    active={active}
                    onRemove={(key) => {
                        if (key === 'search') {
                            setSearch('');
                        } else {
                            setLevel('');
                        }

                        list.apply({
                            ...filters,
                            page: undefined,
                            [key]: undefined,
                        });
                    }}
                    onReset={() => {
                        setSearch('');
                        setLevel('');
                        list.reset();
                    }}
                    defaultAdvancedOpen={filters.level !== undefined}
                >
                    <div className="grid gap-1.5">
                        <Label htmlFor="coa-level">Level</Label>
                        <select
                            id="coa-level"
                            className={selectClass}
                            value={level}
                            onChange={(event) => {
                                setLevel(event.target.value);
                            }}
                        >
                            <option value="">All</option>
                            {LEVELS.map((value) => (
                                <option key={value} value={value}>
                                    Level {value}
                                </option>
                            ))}
                        </select>
                    </div>
                </FilterBar>

                <DataTable
                    caption="Chart of accounts"
                    columns={[
                        {
                            key: 'account',
                            header: 'Account',
                            cell: (account) => (
                                <span
                                    className={
                                        INDENT[account.level ?? 4] ?? 'pl-12'
                                    }
                                >
                                    <Link
                                        href={coaShow(account.coaNo)}
                                        className="font-mono text-xs underline-offset-4 hover:underline"
                                    >
                                        {account.coaNo}
                                    </Link>{' '}
                                    {account.description || '—'}
                                </span>
                            ),
                        },
                        {
                            key: 'level',
                            header: 'Level',
                            align: 'right',
                            cell: (account) => account.level ?? '—',
                        },
                        {
                            key: 'currency',
                            header: 'Currency',
                            cell: (account) => account.currency ?? '—',
                        },
                        {
                            key: 'flags',
                            header: 'Use',
                            cell: (account) => (
                                <span className="flex flex-wrap gap-1">
                                    {account.moduleAccount && (
                                        <Badge variant="secondary">
                                            Module
                                        </Badge>
                                    )}
                                    {account.allowBudget && (
                                        <Badge variant="outline">Budget</Badge>
                                    )}
                                </span>
                            ),
                        },
                    ]}
                    rows={accounts}
                    rowKey={(account) => account.coaNo}
                    rowHref={(account) => coaShow(account.coaNo).url}
                    empty="No accounts matched this filter. Try another search or reset the filters."
                />

                <ListPagination
                    pagination={pagination}
                    noun="accounts"
                    onPageChange={list.goToPage}
                />
            </div>
        </>
    );
}

CoaAccountsIndex.layout = {
    breadcrumbs: [{ title: 'Chart of accounts', href: coaIndex() }],
};
