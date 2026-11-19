import { Head, Link } from '@inertiajs/react';
import { ActiveStatusField } from '@/components/active-status-field';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { FilterBar } from '@/components/filter-bar';
import { ListPagination } from '@/components/list-pagination';
import { PageHeader } from '@/components/page-header';
import { useFilterForm } from '@/hooks/use-filter-form';
import {
    index as branchesIndex,
    show as branchesShow,
} from '@/routes/branches';
import type { BranchSummary } from '@/types/branch';
import type { Pagination, PagingFilters } from '@/types/pagination';

type Filters = PagingFilters & {
    search?: string;
    flagActive?: number;
};

type Props = {
    branches: BranchSummary[];
    pagination: Pagination | null;
    filters: Filters;
};

const columns: DataTableColumn<BranchSummary>[] = [
    {
        key: 'code',
        header: 'Code',
        className: 'font-mono text-xs',
        cell: (branch) => branch.code ?? '—',
    },
    {
        key: 'name',
        header: 'Name',
        className: 'font-medium',
        cell: (branch) => (
            <Link
                href={branchesShow(branch.id)}
                className="underline-offset-4 hover:underline"
            >
                {branch.name || `#${String(branch.id)}`}
            </Link>
        ),
    },
];

export default function BranchesIndex({
    branches,
    pagination,
    filters,
}: Props) {
    const form = useFilterForm(branchesIndex().url, filters, [
        { key: 'search', chip: (value) => `“${value}”` },
        {
            key: 'flagActive',
            chip: (value) => (value === '1' ? 'Active' : 'Inactive'),
        },
    ]);

    return (
        <>
            <Head title="Branches" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Branches"
                    description="Outlets, warehouses and offices, from ESB Core."
                />

                <FilterBar
                    id="branches"
                    search={{
                        label: 'Branch name or code',
                        value: form.values.search!,
                        onChange: (value) => form.set('search', value),
                        placeholder: 'Branch name or code',
                    }}
                    {...form.bar}
                    defaultAdvancedOpen={filters.flagActive !== undefined}
                >
                    <ActiveStatusField
                        id="branches-status"
                        value={form.values.flagActive!}
                        onChange={(flagActive) => {
                            form.set('flagActive', flagActive);
                            form.apply({ ...form.values, flagActive });
                        }}
                    />
                </FilterBar>

                <DataTable
                    caption="Branches"
                    columns={columns}
                    rows={branches}
                    rowKey={(branch) => String(branch.id)}
                    rowHref={(branch) => branchesShow(branch.id).url}
                    empty="No branches matched."
                />

                <ListPagination
                    pagination={pagination}
                    noun="branches"
                    onPageChange={form.goToPage}
                />
            </div>
        </>
    );
}
