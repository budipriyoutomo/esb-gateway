import { Head, Link, router } from '@inertiajs/react';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useListQuery } from '@/hooks/use-list-query';
import { formatAmount, formatDate } from '@/lib/format';
import {
    index as indexRoute,
    summary as summaryRoute,
} from '@/routes/general-ledger';
import type { LedgerEntry } from '@/types/accounting';
import type { Pagination } from '@/types/pagination';

type Filters = {
    page?: number;
    coaNo?: string;
    dateFrom?: string;
    dateTo?: string;
    branchId?: number;
    side?: string;
    reference?: string;
    notes?: string;
    transactionType?: string;
    [key: string]: string | number | undefined;
};

type Props = {
    entries: LedgerEntry[] | null;
    pagination: Pagination | null;
    filters: Filters;
    error: string | null;
    errors?: Record<string, string>;
};

const selectClass =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px]';

/** 0 on the other side of an entry is "nothing", not an amount to read. */
function side(amount: number | null): string {
    return amount === null || amount === 0 ? '—' : formatAmount(amount);
}

const columns: DataTableColumn<LedgerEntry>[] = [
    { key: 'date', header: 'Date', cell: (entry) => formatDate(entry.date) },
    {
        key: 'transaction',
        header: 'Transaction',
        cell: (entry) => (
            <>
                <span className="font-medium">
                    {entry.transactionType ?? '—'}
                </span>
                {entry.reference && (
                    <span className="text-muted-foreground block font-mono text-xs">
                        {entry.reference}
                    </span>
                )}
            </>
        ),
    },
    {
        key: 'notes',
        header: 'Notes',
        cell: (entry) => (
            <>
                {entry.notes ?? '—'}
                {entry.info && entry.info !== entry.notes && (
                    <span className="text-muted-foreground block text-xs">
                        {entry.info}
                    </span>
                )}
            </>
        ),
    },
    {
        key: 'branch',
        header: 'Branch',
        className: 'text-muted-foreground',
        cell: (entry) => entry.branchName ?? '—',
    },
    {
        key: 'debit',
        header: 'Debit',
        align: 'right',
        cell: (entry) => side(entry.debit),
    },
    {
        key: 'credit',
        header: 'Credit',
        align: 'right',
        cell: (entry) => side(entry.credit),
    },
];

/**
 * The general ledger of one account over a period ([ESB-042]). Nothing is
 * asked of the ERP until the account and the period are chosen. Debit and
 * credit are the ERP's; no balance is shown (the ERP gives no opening one).
 */
export default function GeneralLedgerIndex({
    entries,
    pagination,
    filters,
    error,
    errors = {},
}: Props) {
    const list = useListQuery(indexRoute().url, filters);
    const [account, setAccount] = useState<{
        coaNo: string;
        label: string;
    } | null>(
        filters.coaNo ? { coaNo: filters.coaNo, label: filters.coaNo } : null,
    );
    const [dates, setDates] = useState({
        from: filters.dateFrom ?? '',
        to: filters.dateTo ?? '',
    });
    const [branch, setBranch] = useState<{ id: number; name: string } | null>(
        filters.branchId === undefined
            ? null
            : { id: filters.branchId, name: `Branch #${filters.branchId}` },
    );
    const [sideFilter, setSideFilter] = useState(filters.side ?? '');
    const [reference, setReference] = useState(filters.reference ?? '');

    function submit(event: FormEvent) {
        event.preventDefault();
        list.apply({
            coaNo: account?.coaNo,
            dateFrom: dates.from,
            dateTo: dates.to,
            branchId: branch?.id,
            side: sideFilter,
            reference,
        });
    }

    return (
        <>
            <Head title="General ledger" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="General ledger"
                    description="One account's entries over a period, read live from the ERP."
                    actions={
                        <Button variant="outline" asChild>
                            <Link href={summaryRoute().url}>Summary</Link>
                        </Button>
                    }
                />

                <form
                    onSubmit={submit}
                    className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                    aria-label="Ledger filters"
                >
                    <div className="grid gap-1.5">
                        <LookupCombobox
                            id="ledger-account"
                            label="Account"
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
                            placeholder="Choose an account"
                        />
                        <InputError message={errors.coaNo} />
                    </div>
                    <div className="grid gap-1.5">
                        <DateRangeField
                            id="ledger-period"
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
                        id="ledger-branch"
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
                        <Label htmlFor="ledger-side">Side</Label>
                        <select
                            id="ledger-side"
                            className={selectClass}
                            value={sideFilter}
                            onChange={(event) =>
                                setSideFilter(event.target.value)
                            }
                        >
                            <option value="">Debit and credit</option>
                            <option value="debit">Debit only</option>
                            <option value="credit">Credit only</option>
                        </select>
                    </div>
                    <div className="grid gap-1.5">
                        <Label htmlFor="ledger-reference">
                            Reference (optional)
                        </Label>
                        <Input
                            id="ledger-reference"
                            maxLength={100}
                            value={reference}
                            onChange={(event) =>
                                setReference(event.target.value)
                            }
                        />
                    </div>
                    <div className="flex items-end gap-2">
                        <Button type="submit">Show ledger</Button>
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

                {entries === null ? (
                    error === null && (
                        <p className="text-muted-foreground text-sm">
                            Choose an account and a period (at most 366 days) to
                            read its ledger.
                        </p>
                    )
                ) : (
                    <>
                        <DataTable
                            caption="Ledger entries"
                            columns={columns}
                            rows={entries}
                            rowKey={(entry, index) =>
                                `${entry.reference ?? 'entry'}-${index}`
                            }
                            empty="The ERP has no entries on this account for this period."
                        />
                        <ListPagination
                            pagination={pagination}
                            noun="entries"
                            onPageChange={list.goToPage}
                        />
                    </>
                )}
            </div>
        </>
    );
}
