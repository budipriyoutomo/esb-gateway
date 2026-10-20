import { useState } from 'react';
import type { ReactNode } from 'react';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { FilterBar } from '@/components/filter-bar';
import type { ActiveFilter } from '@/components/filter-bar';
import { ListPagination } from '@/components/list-pagination';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { useListQuery } from '@/hooks/use-list-query';
import type { Pagination, PagingFilters } from '@/types/pagination';

export type ReferenceDataFilters = PagingFilters & {
    search?: string;
    flagActive?: number;
    [key: string]: string | number | undefined;
};

type ReferenceRow = { id: number | null; active: boolean | null };

type ExtraFilter = {
    /** Query key, e.g. "categoryTypeId". */
    key: string;
    label: string;
    /** id → label */
    options: Record<number, string>;
};

type Props<T extends ReferenceRow> = {
    /** Prefix for element ids. */
    id: string;
    title: string;
    description: string;
    /** The index route. */
    url: string;
    filters: ReferenceDataFilters;
    pagination: Pagination | null;
    rows: T[];
    /** Columns before the status column. */
    columns: DataTableColumn<T>[];
    detailUrl: (id: number) => string;
    /** Plural noun, e.g. "units". */
    noun: string;
    searchPlaceholder: string;
    /** Page actions, e.g. a "New" button, right of the title. */
    actions?: ReactNode;
    extraFilter?: ExtraFilter;
};

const selectClass =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px]';

function ActiveBadge({ active }: { active: boolean | null }) {
    if (active === null) {
        return <span className="text-muted-foreground">—</span>;
    }

    return active ? (
        <Badge variant="secondary">Active</Badge>
    ) : (
        <Badge variant="outline" className="text-muted-foreground">
            Inactive
        </Badge>
    );
}

/**
 * The index page of a read-only master data list ([ESB-020]–[ESB-023]):
 * search, an active/inactive filter (plus one optional choice filter),
 * a table whose rows open the detail, and paging.
 */
export function ReferenceDataIndex<T extends ReferenceRow>({
    id,
    title,
    description,
    url,
    filters,
    pagination,
    rows,
    columns,
    detailUrl,
    noun,
    searchPlaceholder,
    extraFilter,
    actions,
}: Props<T>) {
    const list = useListQuery(url, filters);
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(
        filters.flagActive === undefined ? '' : String(filters.flagActive),
    );
    const [extra, setExtra] = useState(
        extraFilter && filters[extraFilter.key] !== undefined
            ? String(filters[extraFilter.key])
            : '',
    );

    const extraValue = extraFilter ? filters[extraFilter.key] : undefined;
    const active: ActiveFilter[] = [
        ...(filters.search
            ? [{ key: 'search', label: `“${filters.search}”` }]
            : []),
        ...(filters.flagActive === undefined
            ? []
            : [
                  {
                      key: 'flagActive',
                      label:
                          filters.flagActive === 1
                              ? 'Active only'
                              : 'Inactive only',
                  },
              ]),
        ...(extraFilter && extraValue !== undefined
            ? [
                  {
                      key: extraFilter.key,
                      label: `${extraFilter.label}: ${extraFilter.options[Number(extraValue)] ?? extraValue}`,
                  },
              ]
            : []),
    ];

    // Fields keep their own state across visits (preserveState), so a
    // removed filter is cleared in its field too.
    function clearField(key: string) {
        if (key === 'search') {
            setSearch('');
        } else if (key === 'flagActive') {
            setStatus('');
        } else if (extraFilter && key === extraFilter.key) {
            setExtra('');
        }
    }

    const tableColumns: DataTableColumn<T>[] = [
        ...columns,
        {
            key: 'active',
            header: 'Status',
            cell: (row) => <ActiveBadge active={row.active} />,
        },
    ];

    return (
        <div className="flex h-full flex-1 flex-col gap-6 p-4">
            <PageHeader
                title={title}
                description={description}
                actions={actions}
            />

            <FilterBar
                id={id}
                search={{
                    value: search,
                    onChange: setSearch,
                    placeholder: searchPlaceholder,
                }}
                onSubmit={() => {
                    list.apply({
                        search,
                        flagActive: status === '' ? undefined : Number(status),
                        ...(extraFilter
                            ? {
                                  [extraFilter.key]:
                                      extra === '' ? undefined : Number(extra),
                              }
                            : {}),
                    });
                }}
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
                    setExtra('');
                    list.reset();
                }}
                defaultAdvancedOpen={
                    filters.flagActive !== undefined || extraValue !== undefined
                }
            >
                <div className="grid gap-1.5">
                    <Label htmlFor={`${id}-status`}>Status</Label>
                    <select
                        id={`${id}-status`}
                        className={selectClass}
                        value={status}
                        onChange={(event) => {
                            setStatus(event.target.value);
                        }}
                    >
                        <option value="">All</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                {extraFilter && (
                    <div className="grid gap-1.5">
                        <Label htmlFor={`${id}-${extraFilter.key}`}>
                            {extraFilter.label}
                        </Label>
                        <select
                            id={`${id}-${extraFilter.key}`}
                            className={selectClass}
                            value={extra}
                            onChange={(event) => {
                                setExtra(event.target.value);
                            }}
                        >
                            <option value="">All</option>
                            {Object.entries(extraFilter.options).map(
                                ([value, label]) => (
                                    <option key={value} value={value}>
                                        {label}
                                    </option>
                                ),
                            )}
                        </select>
                    </div>
                )}
            </FilterBar>

            <DataTable
                caption={title}
                columns={tableColumns}
                rows={rows}
                rowKey={(row, index) =>
                    row.id === null ? `row-${index}` : String(row.id)
                }
                rowHref={(row) => (row.id === null ? null : detailUrl(row.id))}
                empty={`No ${noun} matched this filter. Try another search or reset the filters.`}
            />

            <ListPagination
                pagination={pagination}
                noun={noun}
                onPageChange={list.goToPage}
            />
        </div>
    );
}
