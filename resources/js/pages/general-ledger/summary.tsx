import { Head, router } from '@inertiajs/react';
import { TriangleAlert } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { DataTable } from '@/components/data-table';
import type { DataTableColumn } from '@/components/data-table';
import { DateRangeField } from '@/components/date-range-field';
import InputError from '@/components/input-error';
import { ListPagination } from '@/components/list-pagination';
import { LookupCombobox } from '@/components/lookup-combobox';
import { PageHeader } from '@/components/page-header';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useListQuery } from '@/hooks/use-list-query';
import { formatAmount, formatDate } from '@/lib/format';
import {
    index as ledgerRoute,
    summary as summaryRoute,
} from '@/routes/general-ledger';
import type { LedgerSummaryRow } from '@/types/accounting';
import type { Pagination } from '@/types/pagination';

type Filters = {
    page?: number;
    dateFrom?: string;
    dateTo?: string;
    accountNo?: string;
    branchId?: number;
    [key: string]: string | number | undefined;
};

type Props = {
    rows: LedgerSummaryRow[] | null;
    pagination: Pagination | null;
    filters: Filters;
    error: string | null;
    errors?: Record<string, string>;
};

const columns: DataTableColumn<LedgerSummaryRow>[] = [
    { key: 'date', header: 'Date', cell: (row) => formatDate(row.date) },
    {
        key: 'branch',
        header: 'Branch',
        cell: (row) => row.branchName ?? row.branchCode ?? '—',
    },
    {
        key: 'account',
        header: 'Account',
        cell: (row) => (
            <>
                <span className="font-mono text-xs">{row.accountNo}</span>
                {row.accountDescription && (
                    <span className="block">{row.accountDescription}</span>
                )}
            </>
        ),
    },
    {
        key: 'debit',
        header: 'Debit',
        align: 'right',
        cell: (row) => formatAmount(row.debit),
    },
    {
        key: 'credit',
        header: 'Credit',
        align: 'right',
        cell: (row) => formatAmount(row.credit),
    },
];

/**
 * The general ledger summary per branch and account for a period
 * ([ESB-042]): the ERP's own figures, paged by the ERP.
 */
export default function GeneralLedgerSummary({
    rows,
    pagination,
    filters,
    error,
    errors = {},
}: Props) {
    const list = useListQuery(summaryRoute().url, filters);
    const [dates, setDates] = useState({
        from: filters.dateFrom ?? '',
        to: filters.dateTo ?? '',
    });
    const [account, setAccount] = useState<{
        coaNo: string;
        label: string;
    } | null>(
        filters.accountNo
            ? { coaNo: filters.accountNo, label: filters.accountNo }
            : null,
    );
    const [branch, setBranch] = useState<{ id: number; name: string } | null>(
        filters.branchId === undefined
            ? null
            : { id: filters.branchId, name: `Branch #${filters.branchId}` },
    );

    function submit(event: FormEvent) {
        event.preventDefault();
        list.apply({
            dateFrom: dates.from,
            dateTo: dates.to,
            accountNo: account?.coaNo,
            branchId: branch?.id,
        });
    }

    return (
        <>
            <Head title="General ledger summary" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="General ledger summary"
                    description="Debit and credit per branch and account, read live from the ERP."
                    back={{ href: ledgerRoute().url, label: 'General ledger' }}
                />

                <form
                    onSubmit={submit}
                    className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
                    aria-label="Summary filters"
                >
                    <div className="grid gap-1.5">
                        <DateRangeField
                            id="summary-period"
                            label="Period"
                            from={dates.from}
                            to={dates.to}
                            onChange={setDates}
                        />
                        <InputError
                            message={errors.dateFrom ?? errors.dateTo}
                        />
                    </div>
                    <LookupCombobox
                        id="summary-account"
                        label="Account (optional)"
                        kind="coa-accounts"
                        value={account?.coaNo ?? null}
                        selectedLabel={account?.label}
                        onChange={(option) =>
                            setAccount(
                                option
                                    ? {
                                          coaNo: option.value,
                                          label: `${option.value} — ${option.label}`,
                                      }
                                    : null,
                            )
                        }
                        placeholder="All accounts"
                    />
                    <LookupCombobox
                        id="summary-branch"
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
                    <div className="flex items-end gap-2">
                        <Button type="submit">Show summary</Button>
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => router.get(summaryRoute().url)}
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

                {rows === null ? (
                    error === null && (
                        <p className="text-muted-foreground text-sm">
                            Choose a period (at most 366 days) to read its
                            summary.
                        </p>
                    )
                ) : (
                    <>
                        <DataTable
                            caption="Ledger summary"
                            columns={columns}
                            rows={rows}
                            rowKey={(row, index) =>
                                `${row.accountNo}-${row.branchId ?? 'all'}-${row.date ?? index}`
                            }
                            empty="The ERP has nothing posted for this period."
                        />
                        <ListPagination
                            pagination={pagination}
                            noun="rows"
                            onPageChange={list.goToPage}
                        />
                    </>
                )}
            </div>
        </>
    );
}
