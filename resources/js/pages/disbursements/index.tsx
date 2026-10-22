import { Head, Link } from '@inertiajs/react';
import { ExportCsvButton, exportQuery } from '@/components/export-csv-button';
import { useState } from 'react';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { DateRangeField } from '@/components/date-range-field';
import { FilterBar } from '@/components/filter-bar';
import type { ActiveFilter } from '@/components/filter-bar';
import { ImportLinks } from '@/components/import-links';
import { ListPagination } from '@/components/list-pagination';
import { LookupCombobox } from '@/components/lookup-combobox';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { usePermissions } from '@/hooks/use-permissions';
import { useListQuery } from '@/hooks/use-list-query';
import type { ListQuery } from '@/hooks/use-list-query';
import { formatAmount, formatDate } from '@/lib/format';
import {
    exportMethod as exportRoute,
    create as createRoute,
    index as indexRoute,
    show as showRoute,
} from '@/routes/disbursements';
import type { DisbursementSummary } from '@/types/disbursements';
import { PAYMENT } from '@/types/disbursements';
import type { Pagination } from '@/types/pagination';

type Filters = {
    page?: number;
    limit?: number;
    number?: string;
    dateFrom?: string;
    dateTo?: string;
    statusId?: number;
    branchId?: number;
    supplierId?: number;
    paymentId?: number;
    reference?: string;
    overdue?: boolean;
    [key: string]: string | number | boolean | undefined;
};

type Props = {
    disbursements: DisbursementSummary[];
    pagination: Pagination | null;
    filters: Filters;
    statuses: { id: number; name: string }[];
};

type Picked = { id: number; name: string } | null;

const selectClass =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px]';

const PAYMENT_LABELS: Record<number, string> = {
    [PAYMENT.cash]: 'Cash',
    [PAYMENT.credit]: 'Credit',
};

const columns: DataTableColumn<DisbursementSummary>[] = [
    {
        key: 'number',
        header: 'Number',
        className: 'font-mono text-xs',
        cell: (row) => (
            <Link
                href={showRoute(row.number).url}
                className="underline-offset-4 hover:underline"
            >
                {row.number}
            </Link>
        ),
    },
    { key: 'date', header: 'Date', cell: (row) => formatDate(row.date) },
    {
        key: 'branch',
        header: 'Branch',
        className: 'font-medium',
        cell: (row) => row.branchName ?? '—',
    },
    {
        key: 'supplier',
        header: 'Supplier',
        cell: (row) => row.supplierName ?? '—',
    },
    {
        key: 'payment',
        header: 'Payment',
        cell: (row) => (
            <>
                <div>{row.paymentMethod ?? '—'}</div>
                {row.reference && (
                    <div className="text-muted-foreground font-mono text-xs">
                        {row.reference}
                    </div>
                )}
            </>
        ),
    },
    {
        key: 'due',
        header: 'Due',
        cell: (row) =>
            row.invoiceDueDate === null ? (
                '—'
            ) : (
                <>
                    {formatDate(row.invoiceDueDate)}
                    {row.overdue === true && (
                        <span className="text-destructive block text-xs font-medium">
                            Overdue
                        </span>
                    )}
                </>
            ),
    },
    {
        key: 'total',
        header: 'Total',
        align: 'right',
        cell: (row) => formatAmount(row.grandTotal),
    },
    {
        key: 'status',
        header: 'Status',
        cell: (row) => <StatusBadge status={row.statusName} />,
    },
];

/**
 * The disbursement list ([ESB-037]): search by number, and status, branch,
 * supplier, payment method, reference, overdue and date range. Totals are
 * the ERP's.
 */
export default function DisbursementIndex({
    disbursements,
    pagination,
    filters,
    statuses,
}: Props) {
    const { can } = usePermissions();
    const manage = can('esb.invoke') && can('disbursements.manage');
    // The server echoes overdue as true; the query string carries it as 1.
    const query: ListQuery = {
        ...filters,
        overdue: filters.overdue ? 1 : undefined,
    };
    const list = useListQuery(indexRoute().url, query);
    const [search, setSearch] = useState(filters.number ?? '');
    const [status, setStatus] = useState(
        filters.statusId === undefined ? '' : String(filters.statusId),
    );
    const [payment, setPayment] = useState(
        filters.paymentId === undefined ? '' : String(filters.paymentId),
    );
    const [reference, setReference] = useState(filters.reference ?? '');
    const [overdue, setOverdue] = useState(filters.overdue === true);
    const [branch, setBranch] = useState<Picked>(
        filters.branchId === undefined
            ? null
            : { id: filters.branchId, name: `Branch #${filters.branchId}` },
    );
    const [supplier, setSupplier] = useState<Picked>(
        filters.supplierId === undefined
            ? null
            : {
                  id: filters.supplierId,
                  name: `Supplier #${filters.supplierId}`,
              },
    );
    const [dates, setDates] = useState({
        from: filters.dateFrom ?? '',
        to: filters.dateTo ?? '',
    });

    const statusName = (id: number) =>
        statuses.find((option) => option.id === id)?.name ?? `#${id}`;

    // Chips describe what the server applied, not what is being typed.
    const active: ActiveFilter[] = [
        ...(filters.number
            ? [{ key: 'number', label: `“${filters.number}”` }]
            : []),
        ...(filters.statusId === undefined
            ? []
            : [{ key: 'statusId', label: statusName(filters.statusId) }]),
        ...(filters.paymentId === undefined
            ? []
            : [
                  {
                      key: 'paymentId',
                      label: PAYMENT_LABELS[filters.paymentId] ?? 'Payment',
                  },
              ]),
        ...(filters.branchId === undefined
            ? []
            : [{ key: 'branchId', label: branch?.name ?? 'Branch' }]),
        ...(filters.supplierId === undefined
            ? []
            : [{ key: 'supplierId', label: supplier?.name ?? 'Supplier' }]),
        ...(filters.reference
            ? [{ key: 'reference', label: `Ref “${filters.reference}”` }]
            : []),
        ...(filters.overdue ? [{ key: 'overdue', label: 'Overdue' }] : []),
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
        switch (key) {
            case 'number':
                setSearch('');
                break;
            case 'statusId':
                setStatus('');
                break;
            case 'paymentId':
                setPayment('');
                break;
            case 'branchId':
                setBranch(null);
                break;
            case 'supplierId':
                setSupplier(null);
                break;
            case 'reference':
                setReference('');
                break;
            case 'overdue':
                setOverdue(false);
                break;
            case 'dateFrom':
            case 'dateTo':
                setDates((range) => ({
                    ...range,
                    [key === 'dateFrom' ? 'from' : 'to']: '',
                }));
                break;
        }
    }

    return (
        <>
            <Head title="Disbursements" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Disbursements"
                    description="Cash and credit payments out, read live from the ERP."
                    actions={
                        <>
                            <ExportCsvButton
                                href={
                                    exportRoute({ query: exportQuery(filters) })
                                        .url
                                }
                            />
                            {
                                <>
                                    <ImportLinks
                                        canImport={manage}
                                        imports={[
                                            {
                                                kind: 'disbursements',
                                                label: 'Import',
                                            },
                                        ]}
                                    />
                                    {manage && (
                                        <Button asChild>
                                            <Link href={createRoute().url}>
                                                New disbursement
                                            </Link>
                                        </Button>
                                    )}
                                </>
                            }
                        </>
                    }
                />

                <FilterBar
                    id="disbursements"
                    search={{
                        value: search,
                        onChange: setSearch,
                        placeholder: 'Disbursement number',
                    }}
                    onSubmit={() => {
                        list.apply({
                            number: search,
                            statusId:
                                status === '' ? undefined : Number(status),
                            paymentId:
                                payment === '' ? undefined : Number(payment),
                            branchId: branch?.id,
                            supplierId: supplier?.id,
                            reference,
                            overdue: overdue ? 1 : undefined,
                            dateFrom: dates.from,
                            dateTo: dates.to,
                        });
                    }}
                    active={active}
                    onRemove={(key) => {
                        clearField(key);
                        list.apply({
                            ...query,
                            page: undefined,
                            [key]: undefined,
                        });
                    }}
                    onReset={() => {
                        setSearch('');
                        setStatus('');
                        setPayment('');
                        setBranch(null);
                        setSupplier(null);
                        setReference('');
                        setOverdue(false);
                        setDates({ from: '', to: '' });
                        list.reset();
                    }}
                    defaultAdvancedOpen={active.some(
                        (filter) => filter.key !== 'number',
                    )}
                >
                    <div className="grid gap-1.5">
                        <Label htmlFor="disbursements-status">Status</Label>
                        <select
                            id="disbursements-status"
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
                    <div className="grid gap-1.5">
                        <Label htmlFor="disbursements-payment">Payment</Label>
                        <select
                            id="disbursements-payment"
                            className={selectClass}
                            value={payment}
                            onChange={(event) => setPayment(event.target.value)}
                        >
                            <option value="">All</option>
                            <option value={PAYMENT.cash}>Cash</option>
                            <option value={PAYMENT.credit}>Credit</option>
                        </select>
                    </div>
                    <LookupCombobox
                        id="disbursements-branch"
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
                    <LookupCombobox
                        id="disbursements-supplier"
                        label="Supplier"
                        kind="suppliers"
                        value={supplier === null ? null : String(supplier.id)}
                        selectedLabel={supplier?.name}
                        onChange={(option) =>
                            setSupplier(
                                option
                                    ? {
                                          id: Number(option.value),
                                          name: option.label,
                                      }
                                    : null,
                            )
                        }
                        placeholder="All suppliers"
                    />
                    <div className="grid gap-1.5">
                        <Label htmlFor="disbursements-reference">
                            Reference
                        </Label>
                        <input
                            id="disbursements-reference"
                            className={selectClass}
                            maxLength={50}
                            value={reference}
                            onChange={(event) =>
                                setReference(event.target.value)
                            }
                        />
                    </div>
                    <DateRangeField
                        id="disbursements-date"
                        label="Date"
                        from={dates.from}
                        to={dates.to}
                        onChange={setDates}
                    />
                    <div className="flex items-center gap-2 self-end pb-2">
                        <Checkbox
                            id="disbursements-overdue"
                            checked={overdue}
                            onCheckedChange={(checked) =>
                                setOverdue(checked === true)
                            }
                        />
                        <Label htmlFor="disbursements-overdue">
                            Overdue only
                        </Label>
                    </div>
                </FilterBar>

                <DataTable
                    caption="Disbursements"
                    columns={columns}
                    rows={disbursements}
                    rowKey={(row) => row.number}
                    rowHref={(row) => showRoute(row.number).url}
                    empty="No disbursements matched this filter. Try a wider date range or reset the filters."
                />

                <ListPagination
                    pagination={pagination}
                    noun="disbursements"
                    onPageChange={list.goToPage}
                />
            </div>
        </>
    );
}
