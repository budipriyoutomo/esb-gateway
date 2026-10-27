import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { DateRangeField } from '@/components/date-range-field';
import { FilterBar } from '@/components/filter-bar';
import { ImportLinks } from '@/components/import-links';
import type { ActiveFilter } from '@/components/filter-bar';
import { ListPagination } from '@/components/list-pagination';
import { LookupCombobox } from '@/components/lookup-combobox';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { usePermissions } from '@/hooks/use-permissions';
import { useListQuery } from '@/hooks/use-list-query';
import { formatDate } from '@/lib/format';
import {
    createUrl,
    hasReference,
    indexUrl,
    isEditableKind,
    partyLabel,
    showUrl,
    sideLabels,
} from '@/lib/inventory';
import type {
    InventoryDocumentSummary,
    InventoryKind,
} from '@/types/inventory';
import type { Pagination } from '@/types/pagination';

type Filters = {
    page?: number;
    limit?: number;
    number?: string;
    dateFrom?: string;
    dateTo?: string;
    statusId?: number;
    branchId?: number;
    search?: string;
    [key: string]: string | number | undefined;
};

type Props = {
    kind: InventoryKind;
    documents: InventoryDocumentSummary[];
    pagination: Pagination | null;
    filters: Filters;
};

const selectClass =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px]';

function columnsFor(
    kind: InventoryKind['value'],
): DataTableColumn<InventoryDocumentSummary>[] {
    const sides = sideLabels(kind);

    return [
        {
            key: 'number',
            header: 'Number',
            className: 'font-mono text-xs',
            // A pending row has no document yet, so nothing to open.
            cell: (document) =>
                document.number === null ? (
                    <span className="text-muted-foreground">—</span>
                ) : (
                    <Link
                        href={showUrl(kind, document.number)}
                        className="underline-offset-4 hover:underline"
                    >
                        {document.number}
                    </Link>
                ),
        },
        {
            key: 'date',
            header: 'Date',
            cell: (document) => formatDate(document.date),
        },
        {
            key: 'from',
            header: sides.from,
            className: 'font-medium',
            cell: (document) => partyLabel(document.from) ?? '—',
        },
        ...(sides.to === null
            ? []
            : [
                  {
                      key: 'to',
                      header: sides.to,
                      cell: (document: InventoryDocumentSummary) =>
                          partyLabel(document.to) ?? '—',
                  },
              ]),
        ...(hasReference(kind)
            ? [
                  {
                      key: 'reference',
                      header: 'Reference',
                      cell: (document: InventoryDocumentSummary) =>
                          document.reference === null ? (
                              '—'
                          ) : (
                              <>
                                  {document.transactionType && (
                                      <div className="text-muted-foreground text-xs">
                                          {document.transactionType}
                                      </div>
                                  )}
                                  <span className="font-mono text-xs">
                                      {document.reference}
                                  </span>
                              </>
                          ),
                  },
              ]
            : []),
        {
            key: 'notes',
            header: 'Notes',
            className: 'text-muted-foreground',
            cell: (document) => document.notes ?? '—',
        },
        {
            key: 'status',
            header: 'Status',
            cell: (document) => <StatusBadge status={document.statusName} />,
        },
    ];
}

/**
 * The list of one kind of inventory document ([ESB-033], ADR-026): search
 * (by number, or free text where the ERP offers it), status, branch and
 * date range.
 */
export default function InventoryIndex({
    kind,
    documents,
    pagination,
    filters,
}: Props) {
    const { can } = usePermissions();
    const url = indexUrl(kind.value);
    const manage = can('esb.invoke') && can(`${kind.value}.manage`);
    const newHref =
        manage && isEditableKind(kind.value) ? createUrl(kind.value) : null;
    const list = useListQuery(url, filters);
    const searchKey = kind.searchable ? 'search' : 'number';
    const [search, setSearch] = useState(String(filters[searchKey] ?? ''));
    const [status, setStatus] = useState(
        filters.statusId === undefined ? '' : String(filters.statusId),
    );
    const [branch, setBranch] = useState<{ id: number; name: string } | null>(
        filters.branchId === undefined
            ? null
            : { id: filters.branchId, name: `Branch #${filters.branchId}` },
    );
    const [dates, setDates] = useState({
        from: filters.dateFrom ?? '',
        to: filters.dateTo ?? '',
    });

    const statusName = (id: number) =>
        kind.statuses.find((option) => option.id === id)?.name ?? `#${id}`;

    // Chips describe what the server applied, not what is being typed.
    const active: ActiveFilter[] = [
        ...(filters[searchKey]
            ? [{ key: searchKey, label: `“${filters[searchKey]}”` }]
            : []),
        ...(filters.statusId === undefined
            ? []
            : [{ key: 'statusId', label: statusName(filters.statusId) }]),
        ...(filters.branchId === undefined
            ? []
            : [{ key: 'branchId', label: branch?.name ?? 'Branch' }]),
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
        if (key === searchKey) {
            setSearch('');
        } else if (key === 'statusId') {
            setStatus('');
        } else if (key === 'branchId') {
            setBranch(null);
        } else if (key === 'dateFrom' || key === 'dateTo') {
            setDates((range) => ({
                ...range,
                [key === 'dateFrom' ? 'from' : 'to']: '',
            }));
        }
    }

    return (
        <>
            <Head title={kind.pluralLabel} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title={kind.pluralLabel}
                    description="Read live from the ERP."
                    actions={
                        <>
                            {/* The ERP queues a simple transfer sheet ([ESB-036]). */}
                            <ImportLinks
                                canImport={
                                    manage && kind.value === 'simple-transfers'
                                }
                                imports={[
                                    {
                                        kind: 'simple-transfers',
                                        label: 'Import',
                                    },
                                ]}
                            />
                            {newHref !== null && (
                                <Button asChild>
                                    <Link href={newHref}>
                                        New {kind.label.toLowerCase()}
                                    </Link>
                                </Button>
                            )}
                        </>
                    }
                />

                <FilterBar
                    id={kind.value}
                    search={{
                        value: search,
                        onChange: setSearch,
                        placeholder: kind.searchable
                            ? 'Number, branch, notes…'
                            : 'Document number',
                    }}
                    onSubmit={() => {
                        list.apply({
                            [searchKey]: search,
                            statusId:
                                status === '' ? undefined : Number(status),
                            branchId: branch?.id,
                            dateFrom: dates.from,
                            dateTo: dates.to,
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
                        setBranch(null);
                        setDates({ from: '', to: '' });
                        list.reset();
                    }}
                    defaultAdvancedOpen={
                        filters.statusId !== undefined ||
                        filters.branchId !== undefined ||
                        Boolean(filters.dateFrom || filters.dateTo)
                    }
                >
                    <div className="grid gap-1.5">
                        <Label htmlFor={`${kind.value}-status`}>Status</Label>
                        <select
                            id={`${kind.value}-status`}
                            className={selectClass}
                            value={status}
                            onChange={(event) => setStatus(event.target.value)}
                        >
                            <option value="">All</option>
                            {kind.statuses.map((option) => (
                                <option key={option.id} value={option.id}>
                                    {option.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    {kind.filtersByBranch && (
                        <LookupCombobox
                            id={`${kind.value}-branch`}
                            label="Branch"
                            kind="branches"
                            value={branch === null ? null : String(branch.id)}
                            selectedLabel={branch?.name}
                            onChange={(option) =>
                                setBranch(
                                    option
                                        ? {
                                              id: Number(option.value),
                                              name: option.label,
                                          }
                                        : null,
                                )
                            }
                            placeholder="All branches"
                        />
                    )}
                    <DateRangeField
                        id={`${kind.value}-date`}
                        label="Date"
                        from={dates.from}
                        to={dates.to}
                        onChange={setDates}
                    />
                </FilterBar>

                <DataTable
                    caption={kind.pluralLabel}
                    columns={columnsFor(kind.value)}
                    rows={documents}
                    rowKey={(document, index) =>
                        document.number ??
                        `pending-${document.reference}-${index}`
                    }
                    rowHref={(document) =>
                        document.number === null
                            ? null
                            : showUrl(kind.value, document.number)
                    }
                    empty={`No ${kind.pluralLabel.toLowerCase()} matched this filter. Try a wider date range or reset the filters.`}
                />

                <ListPagination
                    pagination={pagination}
                    noun={kind.pluralLabel.toLowerCase()}
                    onPageChange={list.goToPage}
                />
            </div>
        </>
    );
}
