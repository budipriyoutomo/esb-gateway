import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';
import { useEffect } from 'react';
import AlertError from '@/components/alert-error';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    create as createPurchaseRequest,
    edit as editPurchaseRequest,
    index as purchaseRequestsIndex,
    show as showPurchaseRequest,
    store as storePurchaseRequest,
    update as updatePurchaseRequest,
} from '@/routes/purchase-requests';
import { formatQuantity } from '@/lib/format';

type Branch = { id: number; name: string; code: string | null };

type RequestTemplate = { id: number; name: string };

type TemplateLine = {
    productDetailId: number;
    requestProcessId: number;
    requestProcessName: string | null;
    productCode: string;
    productName: string;
    uomName: string | null;
    group: string | null;
    stock: number | null;
    suggestionQty: number | null;
};

type ExistingLine = {
    productDetailId: number | null;
    requestProcessId: number | null;
    productCode: string;
    productName: string;
    uomName: string | null;
    qty: number;
    notes: string | null;
    stock: number | null;
    suggestionQty: number | null;
};

type ExistingPurchaseRequest = {
    number: string;
    date: string | null;
    requiredDate: string | null;
    branchId: number | null;
    branchName: string | null;
    costCenterId: number | null;
    projectId: number | null;
    templateId: number | null;
    isTemplate: boolean;
    notes: string | null;
    lines: ExistingLine[];
};

type Props = {
    purchaseRequest: ExistingPurchaseRequest | null;
    branches: Branch[];
    templates: RequestTemplate[];
    templateLines: TemplateLine[];
    selection: { branchId: number | null; templateId: number | null };
};

/** One row of the lines table. Only rows with a quantity are sent. */
type FormLine = {
    // null when an existing line came back without one; the server refuses
    // such a row with a message saying so.
    productDetailId: number | null;
    requestProcessId: number | null;
    productCode: string;
    productName: string;
    uomName: string | null;
    processName: string | null;
    stock: number | null;
    suggestionQty: number | null;
    qty: string;
    notes: string;
};

type FormData = {
    branchId: string;
    date: string;
    requiredDate: string;
    templateId: string;
    notes: string;
    costCenterId: number | null;
    projectId: number | null;
    isTemplate: boolean;
    lines: FormLine[];
};

/**
 * A date input's value, from the string the ERP sent. The format is not
 * documented; anything not starting with YYYY-MM-DD is left for the user to
 * fill in again rather than guessed at.
 */
function toDateInput(value: string | null): string {
    const match = value?.match(/^\d{4}-\d{2}-\d{2}/);

    return match ? match[0] : '';
}

function today(): string {
    const now = new Date();
    const pad = (part: number) => String(part).padStart(2, '0');

    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

function lineKey(line: {
    productDetailId: number | null;
    requestProcessId: number | null;
}): string {
    return `${line.productDetailId ?? 'x'}:${line.requestProcessId ?? 'x'}`;
}

function hasQty(line: FormLine): boolean {
    return line.qty.trim() !== '' && Number(line.qty) > 0;
}

function fromExisting(line: ExistingLine): FormLine {
    return {
        productDetailId: line.productDetailId,
        requestProcessId: line.requestProcessId,
        productCode: line.productCode,
        productName: line.productName,
        uomName: line.uomName,
        processName: null,
        stock: line.stock,
        suggestionQty: line.suggestionQty,
        qty: String(line.qty),
        notes: line.notes ?? '',
    };
}

function fromTemplate(line: TemplateLine): FormLine {
    return {
        productDetailId: line.productDetailId,
        requestProcessId: line.requestProcessId,
        productCode: line.productCode,
        productName: line.productName,
        uomName: line.uomName,
        processName: line.requestProcessName,
        stock: line.stock,
        suggestionQty: line.suggestionQty,
        qty: '',
        notes: '',
    };
}

const textareaClass =
    'border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-20 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50';

export default function PurchaseRequestForm({
    purchaseRequest: existing,
    branches,
    templates,
    templateLines,
    selection,
}: Props) {
    const isEdit = existing !== null;

    const form = useForm<FormData>({
        branchId: selection.branchId === null ? '' : String(selection.branchId),
        date: existing ? toDateInput(existing.date) : today(),
        requiredDate: existing ? toDateInput(existing.requiredDate) : '',
        templateId:
            existing?.templateId != null ? String(existing.templateId) : '',
        notes: existing?.notes ?? '',
        costCenterId: existing?.costCenterId ?? null,
        projectId: existing?.projectId ?? null,
        isTemplate: existing?.isTemplate ?? false,
        lines: existing ? existing.lines.map(fromExisting) : [],
    });

    // Lines of a newly loaded template join the table, blank, below what is
    // already there. A product already on the request is not added twice.
    // Runs only when a new set of template lines arrives.
    useEffect(() => {
        if (templateLines.length === 0) {
            return;
        }

        form.setData((data) => {
            const present = new Set(data.lines.map(lineKey));
            const added = templateLines
                .filter((line) => !present.has(lineKey(line)))
                .map(fromTemplate);

            return added.length === 0
                ? data
                : { ...data, lines: [...data.lines, ...added] };
        });
    }, [templateLines]);

    const pageUrl = isEdit
        ? editPurchaseRequest(existing.number).url
        : createPurchaseRequest().url;

    /** Ask the server for the lookups that depend on the picked branch/template. */
    function reloadLookups(branchId: string, templateId: string) {
        router.get(
            pageUrl,
            Object.fromEntries(
                Object.entries({ branchId, templateId }).filter(
                    ([, value]) => value !== '',
                ),
            ),
            {
                only: ['templates', 'templateLines', 'selection'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }

    function chooseBranch(branchId: string) {
        // Lines, stock, and suggestions are per branch, so a new branch
        // starts over.
        form.setData((data) => ({
            ...data,
            branchId,
            templateId: '',
            lines: [],
        }));
        reloadLookups(branchId, '');
    }

    function chooseTemplate(templateId: string) {
        form.setData('templateId', templateId);
        reloadLookups(form.data.branchId, templateId);
    }

    function updateLine(index: number, patch: Partial<FormLine>) {
        form.setData(
            'lines',
            form.data.lines.map((line, at) =>
                at === index ? { ...line, ...patch } : line,
            ),
        );
    }

    function removeLine(index: number) {
        form.setData(
            'lines',
            form.data.lines.filter((_, at) => at !== index),
        );
    }

    function applySuggestions() {
        form.setData(
            'lines',
            form.data.lines.map((line) =>
                line.qty.trim() === '' &&
                line.suggestionQty !== null &&
                line.suggestionQty > 0
                    ? { ...line, qty: String(line.suggestionQty) }
                    : line,
            ),
        );
    }

    // Rows without a quantity are not sent, so a server error on
    // lines.N refers to the N-th row that has one.
    const sentIndex = new Map<number, number>();
    form.data.lines.forEach((line, index) => {
        if (hasQty(line)) {
            sentIndex.set(index, sentIndex.size);
        }
    });

    function lineError(index: number, field: string): string | undefined {
        const sent = sentIndex.get(index);

        if (sent === undefined) {
            return undefined;
        }

        const errors = form.errors as Record<string, string | undefined>;

        return errors[`lines.${sent}.${field}`];
    }

    function submit(event: React.FormEvent) {
        event.preventDefault();

        form.transform((data) => ({
            branchId: data.branchId === '' ? null : Number(data.branchId),
            date: data.date,
            requiredDate: data.requiredDate,
            templateId: data.templateId === '' ? null : Number(data.templateId),
            notes: data.notes.trim() === '' ? null : data.notes,
            costCenterId: data.costCenterId,
            projectId: data.projectId,
            isTemplate: data.isTemplate,
            lines: data.lines.filter(hasQty).map((line) => ({
                productDetailId: line.productDetailId,
                requestProcessId: line.requestProcessId,
                qty: Number(line.qty),
                notes: line.notes.trim() === '' ? null : line.notes,
            })),
        }));

        // Not idempotent: the button stays disabled while the request runs,
        // and the server serializes writes too.
        if (isEdit) {
            form.put(updatePurchaseRequest(existing.number).url, {
                preserveScroll: true,
            });
        } else {
            form.post(storePurchaseRequest().url, { preserveScroll: true });
        }
    }

    const errors = form.errors as Record<string, string | undefined>;
    const branchName =
        branches.find((branch) => String(branch.id) === form.data.branchId)
            ?.name ??
        existing?.branchName ??
        null;
    const filledCount = sentIndex.size;
    const title = isEdit ? `Edit ${existing.number}` : 'New purchase request';

    return (
        <>
            <Head title={title} />

            <form
                onSubmit={submit}
                className="flex h-full flex-1 flex-col gap-6 p-4"
            >
                <div>
                    <Button variant="ghost" size="sm" asChild className="-ml-2">
                        <Link
                            href={
                                isEdit
                                    ? showPurchaseRequest(existing.number)
                                    : purchaseRequestsIndex()
                            }
                        >
                            <ArrowLeft className="size-4" />
                            {isEdit ? existing.number : 'Purchase requests'}
                        </Link>
                    </Button>
                </div>

                <Heading
                    title={title}
                    description={
                        isEdit
                            ? 'Saving replaces the request in the ERP with what is below.'
                            : 'Pick a branch and a request template, then fill in the quantities to order.'
                    }
                />

                {errors.form && (
                    <AlertError
                        title="The ERP refused this request."
                        errors={[errors.form]}
                    />
                )}

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="grid gap-1.5">
                        <Label htmlFor="pr-branch">Branch</Label>
                        {isEdit ? (
                            // Lines, stock, and suggestions belong to the
                            // branch, so an edit keeps it.
                            <Input
                                id="pr-branch"
                                value={branchName ?? '—'}
                                disabled
                            />
                        ) : (
                            <Select
                                value={form.data.branchId}
                                onValueChange={chooseBranch}
                            >
                                <SelectTrigger id="pr-branch">
                                    <SelectValue placeholder="Choose a branch" />
                                </SelectTrigger>
                                <SelectContent>
                                    {branches.map((branch) => (
                                        <SelectItem
                                            key={branch.id}
                                            value={String(branch.id)}
                                        >
                                            {branch.name}
                                            {branch.code
                                                ? ` (${branch.code})`
                                                : ''}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        )}
                        <InputError message={errors.branchId} />
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="pr-template">Request template</Label>
                        <Select
                            value={form.data.templateId}
                            onValueChange={chooseTemplate}
                            disabled={form.data.branchId === ''}
                        >
                            <SelectTrigger id="pr-template">
                                <SelectValue
                                    placeholder={
                                        form.data.branchId === ''
                                            ? 'Choose a branch first'
                                            : templates.length === 0
                                              ? 'No templates for this branch'
                                              : 'Choose a template'
                                    }
                                />
                            </SelectTrigger>
                            <SelectContent>
                                {templates.map((template) => (
                                    <SelectItem
                                        key={template.id}
                                        value={String(template.id)}
                                    >
                                        {template.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.templateId} />
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="pr-date">Request date</Label>
                        <Input
                            id="pr-date"
                            type="date"
                            value={form.data.date}
                            onChange={(event) => {
                                form.setData('date', event.target.value);
                            }}
                            required
                        />
                        <InputError message={errors.date} />
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="pr-required">Required date</Label>
                        <Input
                            id="pr-required"
                            type="date"
                            value={form.data.requiredDate}
                            min={form.data.date || undefined}
                            onChange={(event) => {
                                form.setData(
                                    'requiredDate',
                                    event.target.value,
                                );
                            }}
                            required
                        />
                        <InputError message={errors.requiredDate} />
                    </div>
                </div>

                <div className="grid gap-1.5">
                    <Label htmlFor="pr-notes">Notes</Label>
                    <textarea
                        id="pr-notes"
                        className={textareaClass}
                        maxLength={1000}
                        value={form.data.notes}
                        onChange={(event) => {
                            form.setData('notes', event.target.value);
                        }}
                    />
                    <InputError message={errors.notes} />
                </div>

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <p className="text-muted-foreground text-sm">
                        {form.data.lines.length === 0
                            ? 'No products yet.'
                            : `${filledCount} of ${form.data.lines.length} products have a quantity. Only those are sent.`}
                    </p>
                    {form.data.lines.some(
                        (line) => line.suggestionQty !== null,
                    ) && (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={applySuggestions}
                        >
                            Fill empty quantities with suggestions
                        </Button>
                    )}
                </div>
                <InputError message={errors.lines} />

                <div className="border-sidebar-border/70 dark:border-sidebar-border overflow-x-auto rounded-xl border p-2">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Product</TableHead>
                                <TableHead>UoM</TableHead>
                                <TableHead className="text-right">
                                    Stock
                                </TableHead>
                                <TableHead className="text-right">
                                    Suggested
                                </TableHead>
                                <TableHead className="w-32">Qty</TableHead>
                                <TableHead>Notes</TableHead>
                                <TableHead className="w-10">
                                    <span className="sr-only">Remove</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {form.data.lines.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="text-muted-foreground py-8 text-center"
                                    >
                                        Choose a request template to list its
                                        products here.
                                    </TableCell>
                                </TableRow>
                            )}

                            {form.data.lines.map((line, index) => {
                                const lineErrors = [
                                    lineError(index, 'productDetailId'),
                                    lineError(index, 'requestProcessId'),
                                    lineError(index, 'qty'),
                                    lineError(index, 'notes'),
                                ].filter(
                                    (message): message is string =>
                                        message !== undefined,
                                );

                                return (
                                    <TableRow key={lineKey(line) + index}>
                                        <TableCell>
                                            <div className="font-medium">
                                                {line.productName || '—'}
                                            </div>
                                            <div className="text-muted-foreground font-mono text-xs">
                                                {line.productCode}
                                                {line.processName
                                                    ? ` · ${line.processName}`
                                                    : ''}
                                            </div>
                                            {lineErrors.map((message) => (
                                                <InputError
                                                    key={message}
                                                    message={message}
                                                />
                                            ))}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {line.uomName ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground text-right tabular-nums">
                                            {formatQuantity(line.stock)}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground text-right tabular-nums">
                                            {formatQuantity(line.suggestionQty)}
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                type="number"
                                                inputMode="decimal"
                                                min="0"
                                                step="any"
                                                aria-label={`Quantity of ${line.productName}`}
                                                value={line.qty}
                                                onChange={(event) => {
                                                    updateLine(index, {
                                                        qty: event.target.value,
                                                    });
                                                }}
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                maxLength={100}
                                                aria-label={`Notes for ${line.productName}`}
                                                value={line.notes}
                                                onChange={(event) => {
                                                    updateLine(index, {
                                                        notes: event.target
                                                            .value,
                                                    });
                                                }}
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Remove ${line.productName}`}
                                                onClick={() => {
                                                    removeLine(index);
                                                }}
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>
                </div>

                <div className="flex justify-end gap-2">
                    <Button variant="outline" asChild>
                        <Link
                            href={
                                isEdit
                                    ? showPurchaseRequest(existing.number)
                                    : purchaseRequestsIndex()
                            }
                        >
                            Cancel
                        </Link>
                    </Button>
                    <Button
                        type="submit"
                        disabled={form.processing || filledCount === 0}
                    >
                        {form.processing
                            ? 'Sending…'
                            : isEdit
                              ? 'Save changes'
                              : 'Create purchase request'}
                    </Button>
                </div>
            </form>
        </>
    );
}

PurchaseRequestForm.layout = {
    breadcrumbs: [
        {
            title: 'Purchase requests',
            href: purchaseRequestsIndex(),
        },
    ],
};
