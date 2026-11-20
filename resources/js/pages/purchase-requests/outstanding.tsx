import { Head, Link, router } from '@inertiajs/react';
import { TriangleAlert } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { DateRangeField } from '@/components/date-range-field';
import { ExportCsvButton, exportQuery } from '@/components/export-csv-button';
import InputError from '@/components/input-error';
import { ListPagination } from '@/components/list-pagination';
import { LookupCombobox } from '@/components/lookup-combobox';
import { PageHeader } from '@/components/page-header';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useListQuery } from '@/hooks/use-list-query';
import { formatDate, formatQuantity } from '@/lib/format';
import {
    exportMethod as exportRoute,
    index as indexRoute,
} from '@/routes/pr-outstanding';
import { show as purchaseOrderShow } from '@/routes/purchase-orders';
import { show as purchaseRequestShow } from '@/routes/purchase-requests';
import type {
    OutstandingRequestItem,
    OutstandingRequestReport,
    OutstandingStage,
    OutstandingStageValue,
} from '@/types/pr-outstanding';

type Filters = {
    page?: number;
    requiredDateFrom?: string;
    requiredDateTo?: string;
    branchId?: number;
    purchaseRequestNumber?: string;
    stage?: OutstandingStageValue;
    overdueOnly?: boolean;
};

type Props = {
    report: OutstandingRequestReport | null;
    filters: Filters;
    stages: OutstandingStage[];
    error: string | null;
    errors?: Record<string, string>;
};

/** The ERP sends an order's request numbers comma separated. */
function requestNumbers(value: string | null): string[] {
    return (value ?? '')
        .split(',')
        .map((number) => number.trim())
        .filter((number) => number !== '');
}

function columnsFor(
    stages: OutstandingStage[],
): DataTableColumn<OutstandingRequestItem>[] {
    const label = (value: OutstandingStageValue) =>
        stages.find((stage) => stage.value === value)?.label ?? value;

    return [
        {
            key: 'stage',
            header: 'Stage',
            cell: (item) => (
                <Badge
                    variant={
                        item.stage === 'not-ordered' ? 'outline' : 'secondary'
                    }
                >
                    {label(item.stage)}
                </Badge>
            ),
        },
        {
            key: 'documents',
            header: 'PR / PO',
            className: 'font-mono text-xs',
            cell: (item) => (
                <>
                    {requestNumbers(item.purchaseRequestNumbers).map(
                        (number) => (
                            <Link
                                key={number}
                                href={purchaseRequestShow(number)}
                                className="block underline-offset-4 hover:underline"
                            >
                                {number}
                            </Link>
                        ),
                    )}
                    {item.purchaseOrderNumber !== null && (
                        <Link
                            href={purchaseOrderShow(item.purchaseOrderNumber)}
                            className="text-muted-foreground block underline-offset-4 hover:underline"
                        >
                            {item.purchaseOrderNumber}
                        </Link>
                    )}
                </>
            ),
        },
        {
            key: 'product',
            header: 'Product',
            cell: (item) => (
                <>
                    <span className="font-medium">
                        {item.productName ?? '—'}
                    </span>
                    {item.productCode && (
                        <span className="text-muted-foreground block font-mono text-xs">
                            {item.productCode}
                        </span>
                    )}
                </>
            ),
        },
        {
            key: 'branch',
            header: 'Branch / supplier',
            cell: (item) => (
                <>
                    {item.branchName ?? '—'}
                    {item.supplierName && (
                        <span className="text-muted-foreground block text-xs">
                            {item.supplierName}
                        </span>
                    )}
                </>
            ),
        },
        {
            key: 'requested',
            header: 'Requested',
            align: 'right',
            cell: (item) => formatQuantity(item.requestedQty),
        },
        {
            key: 'ordered',
            header: 'Ordered',
            align: 'right',
            cell: (item) => formatQuantity(item.orderedQty),
        },
        {
            key: 'received',
            header: 'Received',
            align: 'right',
            cell: (item) => formatQuantity(item.receivedQty),
        },
        {
            key: 'outstanding',
            header: 'Outstanding',
            align: 'right',
            className: 'font-medium',
            cell: (item) => (
                <>
                    <span>{formatQuantity(item.outstandingQty)}</span>
                    {item.uomName && (
                        <span className="text-muted-foreground block text-xs">
                            {item.uomName}
                        </span>
                    )}
                </>
            ),
        },
        {
            key: 'required',
            header: 'Required',
            cell: (item) => (
                <>
                    {formatDate(item.requiredDate)}
                    {item.overdueDays > 0 && (
                        <span className="text-destructive block text-xs">
                            {item.overdueDays === 1
                                ? '1 day overdue'
                                : `${item.overdueDays} days overdue`}
                        </span>
                    )}
                </>
            ),
        },
        {
            key: 'age',
            header: 'Age',
            align: 'right',
            cell: (item) => (item.ageDays === null ? '—' : `${item.ageDays} d`),
        },
    ];
}

/**
 * PR outstanding (ADR-034): request items not ordered yet, on an open order
 * and not (fully) received, or received not as requested — and for how
 * long. Nothing is asked of the ERP until a required-date period is chosen.
 */
export default function OutstandingRequests({
    report,
    filters,
    stages,
    error,
    errors = {},
}: Props) {
    const list = useListQuery(indexRoute().url, {
        ...filters,
        overdueOnly: filters.overdueOnly ? 1 : undefined,
    });
    const [dates, setDates] = useState({
        from: filters.requiredDateFrom ?? '',
        to: filters.requiredDateTo ?? '',
    });
    const [branch, setBranch] = useState<{ id: number; name: string } | null>(
        filters.branchId === undefined
            ? null
            : { id: filters.branchId, name: `Branch #${filters.branchId}` },
    );
    const [number, setNumber] = useState(filters.purchaseRequestNumber ?? '');
    const [overdueOnly, setOverdueOnly] = useState(
        Boolean(filters.overdueOnly),
    );

    const applied = {
        requiredDateFrom: filters.requiredDateFrom,
        requiredDateTo: filters.requiredDateTo,
        branchId: filters.branchId,
        purchaseRequestNumber: filters.purchaseRequestNumber,
        overdueOnly: filters.overdueOnly ? 1 : undefined,
    };

    function submit(event: FormEvent) {
        event.preventDefault();
        list.apply({
            requiredDateFrom: dates.from,
            requiredDateTo: dates.to,
            branchId: branch?.id,
            purchaseRequestNumber: number,
            stage: filters.stage,
            overdueOnly: overdueOnly ? 1 : undefined,
        });
    }

    const total = report
        ? Object.values(report.stageCounts).reduce((sum, n) => sum + n, 0)
        : 0;

    return (
        <>
            <Head title="PR outstanding" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="PR outstanding"
                    description="Request items not yet ordered, ordered but not received, or received not as requested — read live from the ERP."
                    actions={
                        report !== null && (
                            <ExportCsvButton
                                href={
                                    exportRoute({
                                        query: exportQuery({
                                            ...filters,
                                            overdueOnly: filters.overdueOnly
                                                ? 1
                                                : undefined,
                                        }),
                                    }).url
                                }
                            />
                        )
                    }
                />

                <form
                    onSubmit={submit}
                    className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
                    aria-label="Report filters"
                >
                    <div className="grid gap-1.5">
                        <DateRangeField
                            id="outstanding-required"
                            label="Required date"
                            from={dates.from}
                            to={dates.to}
                            onChange={setDates}
                        />
                        <InputError
                            message={
                                errors.requiredDateFrom ?? errors.requiredDateTo
                            }
                        />
                    </div>
                    <LookupCombobox
                        id="outstanding-branch"
                        label="Branch (optional)"
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
                    <div className="grid gap-1.5">
                        <Label htmlFor="outstanding-number">
                            PR number (optional)
                        </Label>
                        <Input
                            id="outstanding-number"
                            maxLength={64}
                            value={number}
                            onChange={(event) => setNumber(event.target.value)}
                        />
                        <InputError message={errors.purchaseRequestNumber} />
                    </div>
                    <div className="flex items-center gap-2 self-end pb-2">
                        <Checkbox
                            id="outstanding-overdue"
                            checked={overdueOnly}
                            onCheckedChange={(checked) =>
                                setOverdueOnly(checked === true)
                            }
                        />
                        <Label htmlFor="outstanding-overdue">
                            Overdue only
                        </Label>
                    </div>
                    <div className="flex items-end gap-2">
                        <Button type="submit">Show report</Button>
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => router.get(indexRoute().url)}
                        >
                            Reset
                        </Button>
                    </div>
                </form>

                {error !== null && (
                    <Alert variant="destructive" role="alert">
                        <TriangleAlert aria-hidden />
                        <AlertDescription>{error}</AlertDescription>
                    </Alert>
                )}

                {report === null ? (
                    error === null && (
                        <p className="text-muted-foreground text-sm">
                            Choose a required-date period (at most 92 days) to
                            read the outstanding items.
                        </p>
                    )
                ) : (
                    <>
                        <nav
                            aria-label="Stages"
                            className="flex flex-wrap gap-2"
                        >
                            {[
                                {
                                    value: undefined,
                                    label: 'All',
                                    count: total,
                                },
                                ...stages.map((stage) => ({
                                    value: stage.value,
                                    label: stage.label,
                                    count: report.stageCounts[stage.value] ?? 0,
                                })),
                            ].map((tab) => (
                                <Button
                                    key={tab.label}
                                    type="button"
                                    size="sm"
                                    variant={
                                        filters.stage === tab.value
                                            ? 'default'
                                            : 'outline'
                                    }
                                    aria-pressed={filters.stage === tab.value}
                                    onClick={() =>
                                        list.apply({
                                            ...applied,
                                            stage: tab.value,
                                        })
                                    }
                                >
                                    {`${tab.label} `}
                                    <span className="tabular-nums">
                                        {tab.count}
                                    </span>
                                </Button>
                            ))}
                            {report.overdueCount > 0 && (
                                <span className="text-destructive self-center text-sm">
                                    {report.overdueCount} overdue
                                </span>
                            )}
                        </nav>

                        <DataTable
                            caption="Outstanding items"
                            columns={columnsFor(stages)}
                            rows={report.items}
                            rowKey={(item, index) =>
                                `${item.stage}-${item.purchaseOrderNumber ?? item.purchaseRequestNumbers}-${item.productCode ?? item.productName}-${index}`
                            }
                            empty="Nothing outstanding for these filters."
                        />
                        <ListPagination
                            pagination={report.pagination}
                            noun="items"
                            onPageChange={list.goToPage}
                        />
                    </>
                )}
            </div>
        </>
    );
}
