import { Head, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import InputError from '@/components/input-error';
import { LookupCombobox } from '@/components/lookup-combobox';
import { RecordForm, RecordFormField } from '@/components/record-form';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatAmount } from '@/lib/format';
import type { LookupOption } from '@/lib/lookups';
import {
    index as indexRoute,
    show as showRoute,
    store as storeRoute,
    update as updateRoute,
} from '@/routes/memorial-journals';
import type { JournalLine, MemorialJournal } from '@/types/accounting';

type Props = {
    journal: MemorialJournal | null;
};

type Picked = { id: number | null; label: string | null };

type Account = { coaNo: string | null; label: string | null };

type Line = {
    key: number;
    /** The ERP's line this one replaces, so its cost center and project are kept. */
    id: number | null;
    branch: Picked;
    account: Account;
    currency: Picked;
    rate: number | null;
    debit: number | null;
    credit: number | null;
    notes: string;
};

type FormData = {
    date: string;
    notes: string;
    lines: Line[];
};

/** Mirrors SaveMemorialJournalRequest. */
const MIN_LINES = 2;
const NOTES_MAX = 1000;
const LINE_NOTES_MAX = 100;

const NONE: Picked = { id: null, label: null };
const NO_ACCOUNT: Account = { coaNo: null, label: null };

let nextKey = 0;

function emptyLine(from?: Line): Line {
    nextKey += 1;

    return {
        key: nextKey,
        id: null,
        branch: from?.branch ?? NONE,
        account: NO_ACCOUNT,
        currency: from?.currency ?? NONE,
        rate: from?.rate ?? 1,
        debit: null,
        credit: null,
        notes: '',
    };
}

/**
 * A line the ERP sent. It is on the credit side when it has a credit and no
 * debit; the ERP's 0 on the other side is "empty".
 */
function lineFrom(line: JournalLine): Line {
    const credit = (line.credit ?? 0) !== 0 && (line.debit ?? 0) === 0;

    return {
        ...emptyLine(),
        id: line.id,
        branch:
            line.branchId === null
                ? NONE
                : { id: line.branchId, label: line.branchName },
        account: {
            coaNo: line.coaNo,
            label:
                [line.coaNo, line.coaDescription]
                    .filter((part) => part !== null)
                    .join(' — ') || null,
        },
        currency:
            line.currencyId === null
                ? NONE
                : { id: line.currencyId, label: line.currencyName },
        rate: line.rate ?? 1,
        debit: credit ? null : line.debit,
        credit: credit ? line.credit : null,
        notes: line.notes ?? '',
    };
}

/** The ERP may send a time with the date; the field takes YYYY-MM-DD. */
function day(value: string | null): string {
    return value === null ? '' : value.slice(0, 10);
}

function fromOption(option: LookupOption | null): Picked {
    return option === null
        ? NONE
        : { id: Number(option.value), label: option.label };
}

function accountFrom(option: LookupOption | null): Account {
    return option === null
        ? NO_ACCOUNT
        : {
              coaNo: option.value,
              label: `${option.value} — ${option.label}`,
          };
}

function initial(journal: MemorialJournal | null): FormData {
    if (journal === null) {
        return { date: '', notes: '', lines: [emptyLine(), emptyLine()] };
    }

    const lines = journal.lines.map(lineFrom);

    while (lines.length < MIN_LINES) {
        lines.push(emptyLine());
    }

    return { date: day(journal.date), notes: journal.notes ?? '', lines };
}

/** What the server's SaveMemorialJournalRequest takes. */
function payload(data: FormData) {
    return {
        date: data.date,
        notes: data.notes,
        lines: data.lines.map((line) => ({
            id: line.id,
            branchId: line.branch.id,
            coaNo: line.account.coaNo,
            currencyId: line.currency.id,
            rate: line.rate,
            debit: line.debit,
            credit: line.credit,
            notes: line.notes,
        })),
    };
}

/** A number input's value: '' is "not given". */
function numberOrNull(value: string): number | null {
    return value === '' ? null : Number(value);
}

/**
 * The totals as typed, in each line's own currency -- a hint while
 * editing, not the ERP's figure. Whether the journal balances is the ERP's
 * to decide when it is saved.
 */
function Totals({ lines }: { lines: Line[] }) {
    const debit = lines.reduce((sum, line) => sum + (line.debit ?? 0), 0);
    const credit = lines.reduce((sum, line) => sum + (line.credit ?? 0), 0);
    const difference = Math.abs(debit - credit);

    return (
        <>
            <TableRow>
                <TableCell colSpan={4} className="text-right font-medium">
                    Total
                </TableCell>
                <TableCell className="text-right tabular-nums">
                    {formatAmount(debit)}
                </TableCell>
                <TableCell className="text-right tabular-nums">
                    {formatAmount(credit)}
                </TableCell>
                <TableCell colSpan={2} />
            </TableRow>
            <TableRow>
                <TableCell
                    colSpan={8}
                    className="text-muted-foreground text-sm"
                    aria-live="polite"
                >
                    {difference < 0.00005
                        ? 'Debit and credit balance.'
                        : `Debit and credit differ by ${formatAmount(difference)}. The ERP decides whether it can be saved.`}
                </TableCell>
            </TableRow>
        </>
    );
}

/**
 * Create or edit a memorial journal ([ESB-045], S3): a manual journal of at
 * least two lines, each a debit or a credit on an account. The ERP checks
 * the period, the accounts and the balance; its refusals come back on the
 * fields they name. Each kept line's cost center and project are kept as
 * the ERP has them.
 */
export default function MemorialJournalForm({ journal }: Props) {
    const form = useForm<FormData>(initial(journal));
    const errors = form.errors as Record<string, string | undefined>;
    const { data } = form;
    const title = journal ? `Edit ${journal.number}` : 'New memorial journal';
    const backHref = journal ? showRoute(journal.number).url : indexRoute().url;

    function setLine(key: number, changes: Partial<Line>) {
        form.setData(
            'lines',
            data.lines.map((line) =>
                line.key === key ? { ...line, ...changes } : line,
            ),
        );
    }

    function submit() {
        form.transform((current) => payload(current));

        if (journal) {
            form.put(updateRoute(journal.number).url);
        } else {
            form.post(storeRoute().url);
        }
    }

    return (
        <>
            <Head title={title} />

            <RecordForm
                title={title}
                description="Saved in the ERP straight away; it still has to be authorized there."
                back={{
                    href: backHref,
                    label: journal ? journal.number : 'Memorial journals',
                }}
                cancelHref={backHref}
                submitLabel={journal ? 'Save changes' : 'Create journal'}
                onSubmit={submit}
                processing={form.processing}
                isDirty={form.isDirty}
                formError={errors.form}
            >
                <div className="grid gap-4 sm:grid-cols-2">
                    <RecordFormField id="date" label="Date" error={errors.date}>
                        <Input
                            id="date"
                            type="date"
                            required
                            value={data.date}
                            onChange={(event) =>
                                form.setData('date', event.target.value)
                            }
                        />
                    </RecordFormField>
                    <RecordFormField
                        id="notes"
                        label="Notes"
                        error={errors.notes}
                    >
                        <Input
                            id="notes"
                            maxLength={NOTES_MAX}
                            value={data.notes}
                            onChange={(event) =>
                                form.setData('notes', event.target.value)
                            }
                        />
                    </RecordFormField>
                </div>

                <div className="grid gap-3">
                    <Table aria-label="Journal lines">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Account</TableHead>
                                <TableHead className="w-44">Branch</TableHead>
                                <TableHead className="w-40">Currency</TableHead>
                                <TableHead className="w-28">Rate</TableHead>
                                <TableHead className="w-36 text-right">
                                    Debit
                                </TableHead>
                                <TableHead className="w-36 text-right">
                                    Credit
                                </TableHead>
                                <TableHead>Notes</TableHead>
                                <TableHead className="w-12">
                                    <span className="sr-only">Actions</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {data.lines.map((line, index) => {
                                const n = index + 1;
                                const error = (field: string) =>
                                    errors[`lines.${index}.${field}`];

                                return (
                                    <TableRow key={line.key}>
                                        <TableCell className="align-top">
                                            <LookupCombobox
                                                id={`line-${line.key}-account`}
                                                label={`Account, line ${n}`}
                                                kind="coa-accounts"
                                                value={line.account.coaNo}
                                                selectedLabel={
                                                    line.account.label
                                                }
                                                onChange={(option) =>
                                                    setLine(line.key, {
                                                        account:
                                                            accountFrom(option),
                                                    })
                                                }
                                                placeholder="Search accounts"
                                            />
                                            <InputError
                                                message={error('coaNo')}
                                            />
                                        </TableCell>
                                        <TableCell className="align-top">
                                            <LookupCombobox
                                                id={`line-${line.key}-branch`}
                                                label={`Branch, line ${n}`}
                                                kind="branches"
                                                value={
                                                    line.branch.id === null
                                                        ? null
                                                        : String(line.branch.id)
                                                }
                                                selectedLabel={
                                                    line.branch.label
                                                }
                                                onChange={(option) =>
                                                    setLine(line.key, {
                                                        branch: fromOption(
                                                            option,
                                                        ),
                                                    })
                                                }
                                                placeholder="Branch"
                                            />
                                            <InputError
                                                message={error('branchId')}
                                            />
                                        </TableCell>
                                        <TableCell className="align-top">
                                            <LookupCombobox
                                                id={`line-${line.key}-currency`}
                                                label={`Currency, line ${n}`}
                                                kind="currencies"
                                                value={
                                                    line.currency.id === null
                                                        ? null
                                                        : String(
                                                              line.currency.id,
                                                          )
                                                }
                                                selectedLabel={
                                                    line.currency.label
                                                }
                                                onChange={(option) =>
                                                    setLine(line.key, {
                                                        currency:
                                                            fromOption(option),
                                                    })
                                                }
                                                placeholder="Currency"
                                            />
                                            <InputError
                                                message={error('currencyId')}
                                            />
                                        </TableCell>
                                        <TableCell className="align-top">
                                            <Label
                                                htmlFor={`line-${line.key}-rate`}
                                                className="sr-only"
                                            >
                                                {`Rate, line ${n}`}
                                            </Label>
                                            <Input
                                                id={`line-${line.key}-rate`}
                                                type="number"
                                                inputMode="decimal"
                                                step="0.0001"
                                                min={0}
                                                value={line.rate ?? ''}
                                                onChange={(event) =>
                                                    setLine(line.key, {
                                                        rate: numberOrNull(
                                                            event.target.value,
                                                        ),
                                                    })
                                                }
                                            />
                                            <InputError
                                                message={error('rate')}
                                            />
                                        </TableCell>
                                        <TableCell className="align-top">
                                            <Label
                                                htmlFor={`line-${line.key}-debit`}
                                                className="sr-only"
                                            >
                                                {`Debit, line ${n}`}
                                            </Label>
                                            <Input
                                                id={`line-${line.key}-debit`}
                                                type="number"
                                                inputMode="decimal"
                                                step="0.0001"
                                                min={0}
                                                className="text-right"
                                                value={line.debit ?? ''}
                                                onChange={(event) => {
                                                    const debit = numberOrNull(
                                                        event.target.value,
                                                    );

                                                    // A line is on one side only.
                                                    setLine(line.key, {
                                                        debit,
                                                        ...(debit === null
                                                            ? {}
                                                            : { credit: null }),
                                                    });
                                                }}
                                            />
                                            <InputError
                                                message={error('debit')}
                                            />
                                        </TableCell>
                                        <TableCell className="align-top">
                                            <Label
                                                htmlFor={`line-${line.key}-credit`}
                                                className="sr-only"
                                            >
                                                {`Credit, line ${n}`}
                                            </Label>
                                            <Input
                                                id={`line-${line.key}-credit`}
                                                type="number"
                                                inputMode="decimal"
                                                step="0.0001"
                                                min={0}
                                                className="text-right"
                                                value={line.credit ?? ''}
                                                onChange={(event) => {
                                                    const credit = numberOrNull(
                                                        event.target.value,
                                                    );

                                                    setLine(line.key, {
                                                        credit,
                                                        ...(credit === null
                                                            ? {}
                                                            : { debit: null }),
                                                    });
                                                }}
                                            />
                                            <InputError
                                                message={error('credit')}
                                            />
                                        </TableCell>
                                        <TableCell className="align-top">
                                            <Label
                                                htmlFor={`line-${line.key}-notes`}
                                                className="sr-only"
                                            >
                                                {`Notes, line ${n}`}
                                            </Label>
                                            <Input
                                                id={`line-${line.key}-notes`}
                                                maxLength={LINE_NOTES_MAX}
                                                value={line.notes}
                                                onChange={(event) =>
                                                    setLine(line.key, {
                                                        notes: event.target
                                                            .value,
                                                    })
                                                }
                                            />
                                            <InputError
                                                message={error('notes')}
                                            />
                                        </TableCell>
                                        <TableCell className="align-top">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Remove line ${n}`}
                                                disabled={
                                                    data.lines.length <=
                                                    MIN_LINES
                                                }
                                                onClick={() =>
                                                    form.setData(
                                                        'lines',
                                                        data.lines.filter(
                                                            (l) =>
                                                                l.key !==
                                                                line.key,
                                                        ),
                                                    )
                                                }
                                            >
                                                <Trash2
                                                    className="size-4"
                                                    aria-hidden
                                                />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                );
                            })}
                            <Totals lines={data.lines} />
                        </TableBody>
                    </Table>
                    <InputError message={errors.lines} />
                    <div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                form.setData('lines', [
                                    ...data.lines,
                                    // A new line starts on the last line's branch and currency.
                                    emptyLine(data.lines.at(-1)),
                                ])
                            }
                        >
                            <Plus className="size-4" aria-hidden />
                            Add line
                        </Button>
                    </div>
                </div>
            </RecordForm>
        </>
    );
}
