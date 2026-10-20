import { Head, router, useForm } from '@inertiajs/react';
import { FileSpreadsheet, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
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
import type { LookupOption } from '@/lib/lookups';
import {
    formLines as formLinesRoute,
    index as indexRoute,
    show as showRoute,
    store as storeRoute,
    update as updateRoute,
} from '@/routes/disbursements';
import type { Disbursement, DisbursementLine } from '@/types/disbursements';
import { PAYMENT } from '@/types/disbursements';

type Props = {
    disbursement: Disbursement | null;
};

type Picked = { id: number | null; label: string | null };

type Account = { coaNo: string | null; label: string | null };

type Line = {
    key: number;
    branch: Picked;
    account: Account;
    description: string;
    amount: number | null;
};

type FormData = {
    date: string;
    branch: Picked;
    paymentId: number;
    currency: Picked;
    rate: number | null;
    cashBank: Account;
    supplier: Picked;
    creditTerm: number | null;
    reference: string;
    invoiceDueDate: string;
    notes: string;
    lines: Line[];
};

/** Mirrors SaveDisbursementRequest. */
const NOTES_MAX = 1000;
const REFERENCE_MAX = 50;
const DESCRIPTION_MAX = 100;
const CREDIT_TERM_MAX = 99999;

const NONE: Picked = { id: null, label: null };
const NO_ACCOUNT: Account = { coaNo: null, label: null };

const selectClass =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 h-9 rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px]';

let nextKey = 0;

function emptyLine(branch: Picked = NONE): Line {
    nextKey += 1;

    return {
        key: nextKey,
        branch,
        account: NO_ACCOUNT,
        description: '',
        amount: null,
    };
}

/** A line the ERP sent, from the document or its Excel form. */
function lineFrom(line: DisbursementLine): Line {
    return {
        ...emptyLine(),
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
        description: line.description ?? '',
        amount: line.amount,
    };
}

/** The ERP may send a time with the date; the field takes YYYY-MM-DD. */
function day(value: string | null): string {
    return value === null ? '' : value.slice(0, 10);
}

function picked(id: number | null, label: string | null): Picked {
    return id === null ? NONE : { id, label };
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

function initial(disbursement: Disbursement | null): FormData {
    if (disbursement === null) {
        return {
            date: '',
            branch: NONE,
            paymentId: PAYMENT.cash,
            currency: NONE,
            rate: 1,
            cashBank: NO_ACCOUNT,
            supplier: NONE,
            creditTerm: null,
            reference: '',
            invoiceDueDate: '',
            notes: '',
            lines: [emptyLine()],
        };
    }

    return {
        date: day(disbursement.date),
        branch: picked(disbursement.branchId, disbursement.branchName),
        paymentId: disbursement.paymentId ?? PAYMENT.cash,
        currency: picked(disbursement.currencyId, disbursement.currencyName),
        rate: disbursement.rate ?? 1,
        cashBank: {
            coaNo: disbursement.cashBankCoaNo,
            label: disbursement.cashBankCoaNo,
        },
        supplier: picked(disbursement.supplierId, disbursement.supplierName),
        creditTerm: disbursement.creditTerm,
        reference: disbursement.reference ?? '',
        invoiceDueDate: day(disbursement.invoiceDueDate),
        notes: disbursement.notes ?? '',
        lines:
            disbursement.lines.length === 0
                ? [emptyLine()]
                : disbursement.lines.map(lineFrom),
    };
}

/** What the server's SaveDisbursementRequest takes. */
function payload(data: FormData) {
    const cash = data.paymentId === PAYMENT.cash;

    return {
        date: data.date,
        branchId: data.branch.id,
        paymentId: data.paymentId,
        currencyId: data.currency.id,
        rate: data.rate,
        cashBankCoaNo: cash ? data.cashBank.coaNo : null,
        supplierId: data.supplier.id,
        creditTerm: cash ? null : data.creditTerm,
        reference: cash ? '' : data.reference,
        invoiceDueDate: data.invoiceDueDate,
        notes: data.notes,
        lines: data.lines.map((line) => ({
            branchId: line.branch.id,
            coaNo: line.account.coaNo,
            description: line.description,
            amount: line.amount,
        })),
    };
}

/** A number input's value: '' is "not given". */
function numberOrNull(value: string): number | null {
    return value === '' ? null : Number(value);
}

/**
 * Lines from the ERP's Excel disbursement form ([ESB-040]): the ERP reads
 * the sheet and saves nothing; its rows replace the form's lines, to be
 * checked before saving.
 */
function FormLinesUpload({
    onLines,
}: {
    onLines: (lines: DisbursementLine[]) => void;
}) {
    const [file, setFile] = useState<File | null>(null);
    const [processing, setProcessing] = useState(false);

    return (
        <div className="flex flex-wrap items-end gap-2">
            <div className="grid gap-1.5">
                <Label htmlFor="form-lines-file">
                    Fill lines from the ERP's Excel form
                </Label>
                <Input
                    id="form-lines-file"
                    type="file"
                    accept=".xlsx,.xls"
                    onChange={(event) =>
                        setFile(event.target.files?.[0] ?? null)
                    }
                />
            </div>
            <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={file === null || processing}
                onClick={() => {
                    if (file === null) {
                        return;
                    }

                    router.post(
                        formLinesRoute().url,
                        { file },
                        {
                            forceFormData: true,
                            preserveState: true,
                            preserveScroll: true,
                            onStart: () => setProcessing(true),
                            onFinish: () => setProcessing(false),
                            onFlash: (flash) => {
                                const lines = (flash as Record<string, unknown>)
                                    .disbursementLines;

                                if (Array.isArray(lines) && lines.length > 0) {
                                    onLines(lines as DisbursementLine[]);
                                }
                            },
                        },
                    );
                }}
            >
                <FileSpreadsheet className="size-4" aria-hidden />
                Read lines
            </Button>
        </div>
    );
}

/**
 * Create or edit a disbursement ([ESB-039], S3). Cash goes out of a cash /
 * bank account; credit is owed to a supplier. The ERP checks the period,
 * the accounts and the supplier; its refusals come back on the fields they
 * name. Cost center, project and bank details are kept as the ERP has them.
 */
export default function DisbursementForm({ disbursement }: Props) {
    const form = useForm<FormData>(initial(disbursement));
    const errors = form.errors as Record<string, string | undefined>;
    const { data } = form;
    const cash = data.paymentId === PAYMENT.cash;
    const title = disbursement
        ? `Edit ${disbursement.number}`
        : 'New disbursement';
    const backHref = disbursement
        ? showRoute(disbursement.number).url
        : indexRoute().url;

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

        if (disbursement) {
            form.put(updateRoute(disbursement.number).url);
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
                    label: disbursement ? disbursement.number : 'Disbursements',
                }}
                cancelHref={backHref}
                submitLabel={
                    disbursement ? 'Save changes' : 'Create disbursement'
                }
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
                    <div className="grid gap-2">
                        <LookupCombobox
                            id="branchId"
                            label="Branch"
                            kind="branches"
                            value={
                                data.branch.id === null
                                    ? null
                                    : String(data.branch.id)
                            }
                            selectedLabel={data.branch.label}
                            onChange={(option) =>
                                // Another branch has other cash / bank accounts.
                                form.setData((current) => ({
                                    ...current,
                                    branch: fromOption(option),
                                    cashBank: NO_ACCOUNT,
                                }))
                            }
                            placeholder="Choose a branch"
                        />
                        <InputError message={errors.branchId} />
                    </div>
                    <RecordFormField
                        id="paymentId"
                        label="Payment"
                        error={errors.paymentId}
                    >
                        <select
                            id="paymentId"
                            className={selectClass}
                            value={data.paymentId}
                            onChange={(event) => {
                                const paymentId = Number(event.target.value);

                                // What the other method does not take is cleared.
                                form.setData((current) => ({
                                    ...current,
                                    paymentId,
                                    ...(paymentId === PAYMENT.cash
                                        ? { creditTerm: null, reference: '' }
                                        : { cashBank: NO_ACCOUNT }),
                                }));
                            }}
                        >
                            <option value={PAYMENT.cash}>Cash</option>
                            <option value={PAYMENT.credit}>Credit</option>
                        </select>
                    </RecordFormField>
                    {cash ? (
                        <div className="grid gap-2">
                            <LookupCombobox
                                id="cashBankCoaNo"
                                label="Cash / bank account"
                                kind="cash-bank-accounts"
                                branchId={data.branch.id}
                                value={data.cashBank.coaNo}
                                selectedLabel={data.cashBank.label}
                                onChange={(option) =>
                                    form.setData(
                                        'cashBank',
                                        accountFrom(option),
                                    )
                                }
                                placeholder="Choose an account"
                            />
                            <InputError message={errors.cashBankCoaNo} />
                        </div>
                    ) : (
                        <div className="grid gap-2">
                            <LookupCombobox
                                id="supplierId"
                                label="Supplier"
                                kind="suppliers"
                                value={
                                    data.supplier.id === null
                                        ? null
                                        : String(data.supplier.id)
                                }
                                selectedLabel={data.supplier.label}
                                onChange={(option) =>
                                    form.setData('supplier', fromOption(option))
                                }
                                placeholder="Choose a supplier"
                            />
                            <InputError message={errors.supplierId} />
                        </div>
                    )}
                    {cash && (
                        <div className="grid gap-2">
                            <LookupCombobox
                                id="supplierId"
                                label="Supplier (optional)"
                                kind="suppliers"
                                value={
                                    data.supplier.id === null
                                        ? null
                                        : String(data.supplier.id)
                                }
                                selectedLabel={data.supplier.label}
                                onChange={(option) =>
                                    form.setData('supplier', fromOption(option))
                                }
                                placeholder="None"
                            />
                            <InputError message={errors.supplierId} />
                        </div>
                    )}
                    {!cash && (
                        <>
                            <RecordFormField
                                id="creditTerm"
                                label="Credit term, days (optional)"
                                error={errors.creditTerm}
                            >
                                <Input
                                    id="creditTerm"
                                    type="number"
                                    min={0}
                                    max={CREDIT_TERM_MAX}
                                    step={1}
                                    value={data.creditTerm ?? ''}
                                    onChange={(event) =>
                                        form.setData(
                                            'creditTerm',
                                            numberOrNull(event.target.value),
                                        )
                                    }
                                />
                            </RecordFormField>
                            <RecordFormField
                                id="reference"
                                label="Reference (optional)"
                                error={errors.reference}
                            >
                                <Input
                                    id="reference"
                                    maxLength={REFERENCE_MAX}
                                    value={data.reference}
                                    onChange={(event) =>
                                        form.setData(
                                            'reference',
                                            event.target.value,
                                        )
                                    }
                                />
                            </RecordFormField>
                        </>
                    )}
                    <RecordFormField
                        id="invoiceDueDate"
                        label="Invoice due date (optional)"
                        error={errors.invoiceDueDate}
                    >
                        <Input
                            id="invoiceDueDate"
                            type="date"
                            value={data.invoiceDueDate}
                            onChange={(event) =>
                                form.setData(
                                    'invoiceDueDate',
                                    event.target.value,
                                )
                            }
                        />
                    </RecordFormField>
                    <div className="grid gap-2">
                        <LookupCombobox
                            id="currencyId"
                            label="Currency"
                            kind="currencies"
                            value={
                                data.currency.id === null
                                    ? null
                                    : String(data.currency.id)
                            }
                            selectedLabel={data.currency.label}
                            onChange={(option) =>
                                form.setData('currency', fromOption(option))
                            }
                            placeholder="Choose a currency"
                        />
                        <InputError message={errors.currencyId} />
                    </div>
                    <RecordFormField id="rate" label="Rate" error={errors.rate}>
                        <Input
                            id="rate"
                            type="number"
                            inputMode="decimal"
                            step="0.0001"
                            min={0}
                            value={data.rate ?? ''}
                            onChange={(event) =>
                                form.setData(
                                    'rate',
                                    numberOrNull(event.target.value),
                                )
                            }
                        />
                    </RecordFormField>
                    <div className="sm:col-span-2">
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
                </div>

                <div className="grid gap-3">
                    <FormLinesUpload
                        onLines={(lines) =>
                            form.setData('lines', lines.map(lineFrom))
                        }
                    />
                    <Table aria-label="Account lines">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Account</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead className="w-48">Branch</TableHead>
                                <TableHead className="w-40 text-right">
                                    Amount
                                </TableHead>
                                <TableHead className="w-12">
                                    <span className="sr-only">Actions</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {data.lines.map((line, index) => {
                                const n = index + 1;

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
                                                message={
                                                    errors[
                                                        `lines.${index}.coaNo`
                                                    ]
                                                }
                                            />
                                        </TableCell>
                                        <TableCell className="align-top">
                                            <Label
                                                htmlFor={`line-${line.key}-description`}
                                                className="sr-only"
                                            >
                                                {`Description, line ${n}`}
                                            </Label>
                                            <Input
                                                id={`line-${line.key}-description`}
                                                maxLength={DESCRIPTION_MAX}
                                                value={line.description}
                                                onChange={(event) =>
                                                    setLine(line.key, {
                                                        description:
                                                            event.target.value,
                                                    })
                                                }
                                            />
                                            <InputError
                                                message={
                                                    errors[
                                                        `lines.${index}.description`
                                                    ]
                                                }
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
                                                message={
                                                    errors[
                                                        `lines.${index}.branchId`
                                                    ]
                                                }
                                            />
                                        </TableCell>
                                        <TableCell className="align-top">
                                            <Label
                                                htmlFor={`line-${line.key}-amount`}
                                                className="sr-only"
                                            >
                                                {`Amount, line ${n}`}
                                            </Label>
                                            <Input
                                                id={`line-${line.key}-amount`}
                                                type="number"
                                                inputMode="decimal"
                                                step="0.0001"
                                                className="text-right"
                                                value={line.amount ?? ''}
                                                onChange={(event) =>
                                                    setLine(line.key, {
                                                        amount: numberOrNull(
                                                            event.target.value,
                                                        ),
                                                    })
                                                }
                                            />
                                            <InputError
                                                message={
                                                    errors[
                                                        `lines.${index}.amount`
                                                    ]
                                                }
                                            />
                                        </TableCell>
                                        <TableCell className="align-top">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Remove line ${n}`}
                                                disabled={
                                                    data.lines.length === 1
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
                                    // A new line starts at the disbursement's branch.
                                    emptyLine(data.branch),
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
