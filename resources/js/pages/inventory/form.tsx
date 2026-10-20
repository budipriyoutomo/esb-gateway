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
import {
    indexUrl,
    isEditableKind,
    showUrl,
    storeUrl,
    updateUrl,
} from '@/lib/inventory';
import type { LookupOption } from '@/lib/lookups';
import type {
    EditableInventoryKind,
    InventoryDocument,
    InventoryKind,
} from '@/types/inventory';

type Props = {
    kind: InventoryKind;
    document: InventoryDocument | null;
};

type Line = {
    key: number;
    productDetailId: number | null;
    label: string | null;
    qty: number | null;
    requestQty: number | null;
    purposeId: number | null;
    purposeLabel: string | null;
    cost: number | null;
};

type Picked = { id: number | null; label: string | null };

type FormData = {
    date: string;
    branch: Picked;
    location: Picked;
    destinationBranch: Picked;
    destinationLocation: Picked;
    notes: string;
    lines: Line[];
};

/** Mirrors SaveInventoryDocumentRequest::NOTES_MAX. */
const NOTES_MAX = 1000;

const NONE: Picked = { id: null, label: null };

let nextKey = 0;

function emptyLine(): Line {
    nextKey += 1;

    return {
        key: nextKey,
        productDetailId: null,
        label: null,
        qty: null,
        requestQty: null,
        purposeId: null,
        purposeLabel: null,
        cost: null,
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

function initial(document: InventoryDocument | null): FormData {
    if (document === null) {
        return {
            date: '',
            branch: NONE,
            location: NONE,
            destinationBranch: NONE,
            destinationLocation: NONE,
            notes: '',
            lines: [emptyLine()],
        };
    }

    const { from, to } = document;

    return {
        date: day(document.date),
        branch: picked(from?.branchId ?? null, from?.branchName ?? null),
        location: picked(from?.locationId ?? null, from?.locationName ?? null),
        destinationBranch: picked(to?.branchId ?? null, to?.branchName ?? null),
        destinationLocation: picked(
            to?.locationId ?? null,
            to?.locationName ?? null,
        ),
        notes: document.notes ?? '',
        lines: document.lines.map((line) => ({
            ...emptyLine(),
            productDetailId: line.productDetailId,
            label:
                [line.productName, line.unit]
                    .filter((part) => part !== null)
                    .join(' — ') || null,
            qty: line.qty,
            requestQty: line.requestedQty,
            purposeId: line.purposeId,
            purposeLabel: line.purpose,
            cost: line.cost,
        })),
    };
}

/** What the server's SaveInventoryDocumentRequest takes, per kind. */
function payload(kind: EditableInventoryKind, data: FormData) {
    const common = {
        date: data.date,
        branchId: data.branch.id,
        locationId: data.location.id,
    };

    switch (kind) {
        case 'goods-transfer-requests':
            return {
                ...common,
                destinationBranchId: data.destinationBranch.id,
                notes: data.notes,
                lines: data.lines.map((line) => ({
                    productDetailId: line.productDetailId,
                    qty: line.qty,
                    requestQty: line.requestQty,
                })),
            };
        case 'item-journals':
            return {
                ...common,
                notes: data.notes,
                lines: data.lines.map((line) => ({
                    productDetailId: line.productDetailId,
                    qty: line.qty,
                    purposeId: line.purposeId,
                    cost: line.cost,
                })),
            };
        case 'simple-transfers':
            return {
                ...common,
                destinationLocationId: data.destinationLocation.id,
                notes: data.notes,
                lines: data.lines.map((line) => ({
                    productDetailId: line.productDetailId,
                    qty: line.qty,
                })),
            };
    }
}

/** A number input's value: '' is "not given". */
function numberOrNull(value: string): number | null {
    return value === '' ? null : Number(value);
}

/**
 * Create or edit a goods transfer request, item journal or simple transfer
 * ([ESB-035], S3). The ERP checks stock, periods and what it allows; its
 * refusals come back on the fields they name. Assets are not handled here.
 */
export default function InventoryForm({ kind, document }: Props) {
    const form = useForm<FormData>(initial(document));
    const errors = form.errors as Record<string, string | undefined>;

    if (!isEditableKind(kind.value)) {
        throw new Error(`${kind.value} is not edited through the gateway.`);
    }

    const value: EditableInventoryKind = kind.value;

    const label = kind.label.toLowerCase();
    const title = document ? `Edit ${document.number}` : `New ${label}`;
    const transfer = value === 'goods-transfer-requests';
    const journal = value === 'item-journals';
    const simple = value === 'simple-transfers';
    const { data } = form;

    function setLine(key: number, changes: Partial<Line>) {
        form.setData(
            'lines',
            data.lines.map((line) =>
                line.key === key ? { ...line, ...changes } : line,
            ),
        );
    }

    function submit() {
        form.transform((current) => payload(value, current));

        if (document) {
            form.put(updateUrl(value, document.number));
        } else {
            form.post(storeUrl(value));
        }
    }

    const backHref = document
        ? showUrl(value, document.number)
        : indexUrl(value);

    return (
        <>
            <Head title={title} />

            <RecordForm
                title={title}
                description="Saved in the ERP straight away; it still has to be authorized there."
                back={{
                    href: backHref,
                    label: document ? document.number : kind.pluralLabel,
                }}
                cancelHref={backHref}
                submitLabel={document ? 'Save changes' : `Create ${label}`}
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
                            label={transfer ? 'From branch' : 'Branch'}
                            kind="branches"
                            value={
                                data.branch.id === null
                                    ? null
                                    : String(data.branch.id)
                            }
                            selectedLabel={data.branch.label}
                            onChange={(option) =>
                                // Another branch has other locations.
                                form.setData((current) => ({
                                    ...current,
                                    branch: fromOption(option),
                                    location: NONE,
                                }))
                            }
                            placeholder="Choose a branch"
                        />
                        <InputError message={errors.branchId} />
                    </div>
                    <div className="grid gap-2">
                        <LookupCombobox
                            id="locationId"
                            label={
                                transfer
                                    ? 'From location (optional)'
                                    : simple
                                      ? 'From location'
                                      : 'Location'
                            }
                            kind="locations"
                            branchId={data.branch.id}
                            value={
                                data.location.id === null
                                    ? null
                                    : String(data.location.id)
                            }
                            selectedLabel={data.location.label}
                            onChange={(option) =>
                                form.setData('location', fromOption(option))
                            }
                            placeholder="Choose a location"
                        />
                        <InputError message={errors.locationId} />
                    </div>
                    {transfer && (
                        <div className="grid gap-2">
                            <LookupCombobox
                                id="destinationBranchId"
                                label="To branch"
                                kind="branches"
                                value={
                                    data.destinationBranch.id === null
                                        ? null
                                        : String(data.destinationBranch.id)
                                }
                                selectedLabel={data.destinationBranch.label}
                                onChange={(option) =>
                                    form.setData(
                                        'destinationBranch',
                                        fromOption(option),
                                    )
                                }
                                placeholder="Choose a branch"
                            />
                            <InputError message={errors.destinationBranchId} />
                        </div>
                    )}
                    {simple && (
                        <div className="grid gap-2">
                            <LookupCombobox
                                id="destinationLocationId"
                                label="To location"
                                kind="locations"
                                value={
                                    data.destinationLocation.id === null
                                        ? null
                                        : String(data.destinationLocation.id)
                                }
                                selectedLabel={data.destinationLocation.label}
                                onChange={(option) =>
                                    form.setData(
                                        'destinationLocation',
                                        fromOption(option),
                                    )
                                }
                                placeholder="Choose a location"
                            />
                            <InputError
                                message={errors.destinationLocationId}
                            />
                        </div>
                    )}
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
                    <Table aria-label="Products">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Product and unit</TableHead>
                                <TableHead className="w-32 text-right">
                                    Qty
                                </TableHead>
                                {transfer && (
                                    <TableHead className="w-32 text-right">
                                        Requested
                                    </TableHead>
                                )}
                                {journal && (
                                    <>
                                        <TableHead className="w-56">
                                            Purpose
                                        </TableHead>
                                        <TableHead className="w-36 text-right">
                                            Cost (HPP)
                                        </TableHead>
                                    </>
                                )}
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
                                                id={`line-${line.key}-product`}
                                                label={`Product, line ${n}`}
                                                kind="transfer-products"
                                                branchId={data.branch.id}
                                                value={
                                                    line.productDetailId ===
                                                    null
                                                        ? null
                                                        : String(
                                                              line.productDetailId,
                                                          )
                                                }
                                                selectedLabel={line.label}
                                                onChange={(option) =>
                                                    setLine(line.key, {
                                                        productDetailId: option
                                                            ? Number(
                                                                  option.value,
                                                              )
                                                            : null,
                                                        label: option
                                                            ? [
                                                                  option.label,
                                                                  option.meta
                                                                      .uomName,
                                                              ]
                                                                  .filter(
                                                                      Boolean,
                                                                  )
                                                                  .join(' — ')
                                                            : null,
                                                    })
                                                }
                                                placeholder="Search products"
                                            />
                                            <InputError
                                                message={
                                                    errors[
                                                        `lines.${index}.productDetailId`
                                                    ]
                                                }
                                            />
                                        </TableCell>
                                        <TableCell className="align-top">
                                            <Label
                                                htmlFor={`line-${line.key}-qty`}
                                                className="sr-only"
                                            >
                                                {`Qty, line ${n}`}
                                            </Label>
                                            <Input
                                                id={`line-${line.key}-qty`}
                                                type="number"
                                                inputMode="decimal"
                                                step="0.0001"
                                                // An item journal can take stock out.
                                                min={journal ? undefined : 0}
                                                className="text-right"
                                                value={line.qty ?? ''}
                                                onChange={(event) =>
                                                    setLine(line.key, {
                                                        qty: numberOrNull(
                                                            event.target.value,
                                                        ),
                                                    })
                                                }
                                            />
                                            <InputError
                                                message={
                                                    errors[`lines.${index}.qty`]
                                                }
                                            />
                                        </TableCell>
                                        {transfer && (
                                            <TableCell className="align-top">
                                                <Label
                                                    htmlFor={`line-${line.key}-request`}
                                                    className="sr-only"
                                                >
                                                    {`Requested, line ${n}`}
                                                </Label>
                                                <Input
                                                    id={`line-${line.key}-request`}
                                                    type="number"
                                                    inputMode="decimal"
                                                    step="0.0001"
                                                    min={0}
                                                    className="text-right"
                                                    value={
                                                        line.requestQty ?? ''
                                                    }
                                                    onChange={(event) =>
                                                        setLine(line.key, {
                                                            requestQty:
                                                                numberOrNull(
                                                                    event.target
                                                                        .value,
                                                                ),
                                                        })
                                                    }
                                                />
                                                <InputError
                                                    message={
                                                        errors[
                                                            `lines.${index}.requestQty`
                                                        ]
                                                    }
                                                />
                                            </TableCell>
                                        )}
                                        {journal && (
                                            <>
                                                <TableCell className="align-top">
                                                    <LookupCombobox
                                                        id={`line-${line.key}-purpose`}
                                                        label={`Purpose, line ${n}`}
                                                        kind="purposes"
                                                        value={
                                                            line.purposeId ===
                                                            null
                                                                ? null
                                                                : String(
                                                                      line.purposeId,
                                                                  )
                                                        }
                                                        selectedLabel={
                                                            line.purposeLabel
                                                        }
                                                        onChange={(option) =>
                                                            setLine(line.key, {
                                                                purposeId:
                                                                    option
                                                                        ? Number(
                                                                              option.value,
                                                                          )
                                                                        : null,
                                                                purposeLabel:
                                                                    option?.label ??
                                                                    null,
                                                            })
                                                        }
                                                        placeholder="Optional"
                                                    />
                                                    <InputError
                                                        message={
                                                            errors[
                                                                `lines.${index}.purposeId`
                                                            ]
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell className="align-top">
                                                    <Label
                                                        htmlFor={`line-${line.key}-cost`}
                                                        className="sr-only"
                                                    >
                                                        {`Cost, line ${n}`}
                                                    </Label>
                                                    <Input
                                                        id={`line-${line.key}-cost`}
                                                        type="number"
                                                        inputMode="decimal"
                                                        step="0.0001"
                                                        min={0}
                                                        className="text-right"
                                                        value={line.cost ?? ''}
                                                        onChange={(event) =>
                                                            setLine(line.key, {
                                                                cost: numberOrNull(
                                                                    event.target
                                                                        .value,
                                                                ),
                                                            })
                                                        }
                                                    />
                                                    <InputError
                                                        message={
                                                            errors[
                                                                `lines.${index}.cost`
                                                            ]
                                                        }
                                                    />
                                                </TableCell>
                                            </>
                                        )}
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
                                    emptyLine(),
                                ])
                            }
                        >
                            <Plus className="size-4" aria-hidden />
                            Add product
                        </Button>
                    </div>
                </div>
            </RecordForm>
        </>
    );
}
