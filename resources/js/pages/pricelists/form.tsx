import { Head, useForm } from '@inertiajs/react';
import { Plus, Trash2, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import InputError from '@/components/input-error';
import { LookupCombobox } from '@/components/lookup-combobox';
import { PurchaseHistoryDialog } from '@/components/purchase-history-dialog';
import { RecordForm, RecordFormField } from '@/components/record-form';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
    store as storeRoute,
    index as pricelistsIndex,
} from '@/routes/pricelists';
import {
    index as tempIndex,
    show as tempShow,
    update as updateRoute,
} from '@/routes/pricelists/temp';
import { formatAmount } from '@/lib/format';
import { fetchLastPrices } from '@/lib/pricelist-prices';
import type { TempPricelist } from '@/types/master-data';

type Branch = { id: number; name: string };

type Line = {
    key: number;
    productDetailId: number | null;
    label: string | null;
    price: number | null;
};

type FormData = {
    supplierId: number | null;
    supplierName: string;
    currencyId: number | null;
    priceDate: string;
    expireDate: string;
    allBranches: boolean;
    branches: Branch[];
    lines: Line[];
};

let nextKey = 0;

function emptyLine(): Line {
    nextKey += 1;

    return { key: nextKey, productDetailId: null, label: null, price: null };
}

/** The ERP may send a time with the date; the field takes YYYY-MM-DD. */
function day(value: string | null): string {
    return value === null ? '' : value.slice(0, 10);
}

function branchesOf(pricelist: TempPricelist): Branch[] {
    const { ids, names } = pricelist.branches;

    return ids.map((id, index) => ({
        id,
        name: ids.length === names.length ? names[index] : `Branch #${id}`,
    }));
}

function initial(pricelist: TempPricelist | null): FormData {
    if (pricelist === null) {
        return {
            supplierId: null,
            supplierName: '',
            currencyId: null,
            priceDate: '',
            expireDate: '',
            allBranches: true,
            branches: [],
            lines: [emptyLine()],
        };
    }

    return {
        supplierId: pricelist.supplierId,
        supplierName: pricelist.supplierName ?? '',
        currencyId: pricelist.currencyId,
        priceDate: day(pricelist.priceDate),
        expireDate: day(pricelist.expireDate),
        allBranches: pricelist.branches.all !== false,
        branches: branchesOf(pricelist),
        lines: pricelist.lines.map((line) => ({
            ...emptyLine(),
            productDetailId: line.productDetailId,
            label:
                [line.productName, line.unitName]
                    .filter((part) => part !== null)
                    .join(' — ') || null,
            price: line.price,
        })),
    };
}

/**
 * The ERP's active price of every product on the lines, for the chosen
 * supplier, currency and price date ([ESB-032]). Display only; refetched
 * shortly after any of them changes. A failure just leaves the column empty.
 */
function useActivePrices(data: FormData): Record<number, number | null> {
    const [prices, setPrices] = useState<Record<number, number | null>>({});
    const ids = data.lines
        .map((line) => line.productDetailId)
        .filter((id): id is number => id !== null);
    const key = ids.join(',');

    useEffect(() => {
        const { supplierId, currencyId, priceDate } = data;

        if (
            supplierId === null ||
            currencyId === null ||
            !/^\d{4}-\d{2}-\d{2}$/.test(priceDate) ||
            ids.length === 0
        ) {
            setPrices({});

            return;
        }

        const controller = new AbortController();
        const timer = window.setTimeout(() => {
            fetchLastPrices(
                { supplierId, currencyId, priceDate, productDetailIds: ids },
                controller.signal,
            )
                .then(setPrices)
                .catch(() => {
                    if (!controller.signal.aborted) {
                        setPrices({});
                    }
                });
        }, 300);

        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
        // `ids` is captured through `key`.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [data.supplierId, data.currencyId, data.priceDate, key]);

    return prices;
}

/**
 * Propose a supplier pricelist, or edit a proposed (temporary) one
 * ([ESB-031], S3). A proposal is authorized from its detail page.
 */
export default function PricelistForm({
    pricelist,
}: {
    pricelist: TempPricelist | null;
}) {
    const form = useForm<FormData>(initial(pricelist));
    const activePrices = useActivePrices(form.data);
    const errors = form.errors as Record<string, string | undefined>;
    const title = pricelist ? `Edit ${pricelist.number}` : 'New pricelist';

    function setLine(key: number, changes: Partial<Line>) {
        form.setData(
            'lines',
            form.data.lines.map((line) =>
                line.key === key ? { ...line, ...changes } : line,
            ),
        );
    }

    function submit() {
        form.transform((data) => ({
            supplierId: data.supplierId,
            supplierName: data.supplierName,
            currencyId: data.currencyId,
            priceDate: data.priceDate,
            expireDate: data.expireDate,
            branchIds: data.allBranches
                ? []
                : data.branches.map((branch) => branch.id),
            lines: data.lines.map((line) => ({
                productDetailId: line.productDetailId,
                price: line.price,
            })),
        }));

        if (pricelist) {
            form.put(updateRoute(pricelist.number).url);
        } else {
            form.post(storeRoute().url);
        }
    }

    return (
        <>
            <Head title={title} />

            <RecordForm
                title={title}
                description="A new pricelist waits for approval before its prices apply."
                back={
                    pricelist
                        ? {
                              href: tempShow(pricelist.number),
                              label: pricelist.number,
                          }
                        : { href: tempIndex(), label: 'Proposed pricelists' }
                }
                cancelHref={
                    pricelist ? tempShow(pricelist.number) : tempIndex()
                }
                submitLabel={pricelist ? 'Save changes' : 'Create pricelist'}
                onSubmit={submit}
                processing={form.processing}
                isDirty={form.isDirty}
                formError={errors.form}
            >
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <LookupCombobox
                            id="supplierId"
                            label="Supplier"
                            kind="suppliers"
                            value={
                                form.data.supplierId === null
                                    ? null
                                    : String(form.data.supplierId)
                            }
                            selectedLabel={pricelist?.supplierName}
                            onChange={(option) =>
                                form.setData((data) => ({
                                    ...data,
                                    supplierId: option
                                        ? Number(option.value)
                                        : null,
                                    supplierName: option?.label ?? '',
                                }))
                            }
                            placeholder="Choose a supplier"
                        />
                        <InputError message={errors.supplierId} />
                    </div>
                    <div className="grid gap-2">
                        <LookupCombobox
                            id="currencyId"
                            label="Currency"
                            kind="currencies"
                            value={
                                form.data.currencyId === null
                                    ? null
                                    : String(form.data.currencyId)
                            }
                            selectedLabel={pricelist?.currencyName}
                            onChange={(option) =>
                                form.setData(
                                    'currencyId',
                                    option ? Number(option.value) : null,
                                )
                            }
                            placeholder="Choose a currency"
                        />
                        <InputError message={errors.currencyId} />
                    </div>
                    <RecordFormField
                        id="priceDate"
                        label="Price date"
                        error={errors.priceDate}
                    >
                        <Input
                            id="priceDate"
                            type="date"
                            required
                            value={form.data.priceDate}
                            onChange={(event) =>
                                form.setData('priceDate', event.target.value)
                            }
                        />
                    </RecordFormField>
                    <RecordFormField
                        id="expireDate"
                        label="Expires"
                        error={errors.expireDate}
                    >
                        <Input
                            id="expireDate"
                            type="date"
                            required
                            value={form.data.expireDate}
                            onChange={(event) =>
                                form.setData('expireDate', event.target.value)
                            }
                        />
                    </RecordFormField>
                </div>

                <fieldset className="grid gap-3">
                    <legend className="mb-2 text-base font-medium">
                        Branches
                    </legend>
                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="allBranches"
                            checked={form.data.allBranches}
                            onCheckedChange={(checked) =>
                                form.setData('allBranches', checked === true)
                            }
                        />
                        <Label htmlFor="allBranches">All branches</Label>
                    </div>
                    {!form.data.allBranches && (
                        <>
                            <div className="flex flex-wrap gap-2">
                                {form.data.branches.map((branch) => (
                                    <Badge
                                        key={branch.id}
                                        variant="secondary"
                                        className="gap-1"
                                    >
                                        {branch.name}
                                        <button
                                            type="button"
                                            aria-label={`Remove ${branch.name}`}
                                            onClick={() =>
                                                form.setData(
                                                    'branches',
                                                    form.data.branches.filter(
                                                        (b) =>
                                                            b.id !== branch.id,
                                                    ),
                                                )
                                            }
                                        >
                                            <X className="size-3" aria-hidden />
                                        </button>
                                    </Badge>
                                ))}
                            </div>
                            <LookupCombobox
                                id="addBranch"
                                label="Add a branch"
                                kind="branches"
                                value={null}
                                onChange={(option) => {
                                    if (
                                        option &&
                                        !form.data.branches.some(
                                            (b) =>
                                                b.id === Number(option.value),
                                        )
                                    ) {
                                        form.setData('branches', [
                                            ...form.data.branches,
                                            {
                                                id: Number(option.value),
                                                name: option.label,
                                            },
                                        ]);
                                    }
                                }}
                                placeholder="Search branches"
                            />
                        </>
                    )}
                    <InputError message={errors.branchIds} />
                </fieldset>

                <div className="grid gap-3">
                    <Table aria-label="Prices">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Product and unit</TableHead>
                                <TableHead className="w-32 text-right">
                                    Active price
                                </TableHead>
                                <TableHead className="w-40 text-right">
                                    Price
                                </TableHead>
                                <TableHead className="w-24">
                                    <span className="sr-only">Actions</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {form.data.lines.map((line, index) => (
                                <TableRow key={line.key}>
                                    <TableCell className="align-top">
                                        <LookupCombobox
                                            id={`line-${line.key}-product`}
                                            label={`Product, line ${index + 1}`}
                                            kind="product-details"
                                            value={
                                                line.productDetailId === null
                                                    ? null
                                                    : String(
                                                          line.productDetailId,
                                                      )
                                            }
                                            selectedLabel={line.label}
                                            onChange={(option) =>
                                                setLine(line.key, {
                                                    productDetailId: option
                                                        ? Number(option.value)
                                                        : null,
                                                    label: option
                                                        ? [
                                                              option.label,
                                                              option.meta
                                                                  .uomName,
                                                          ]
                                                              .filter(Boolean)
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
                                    <TableCell
                                        className="text-muted-foreground pt-4 text-right align-top tabular-nums"
                                        aria-label={`Active price, line ${index + 1}`}
                                    >
                                        {line.productDetailId === null
                                            ? '—'
                                            : formatAmount(
                                                  activePrices[
                                                      line.productDetailId
                                                  ] ?? null,
                                              )}
                                    </TableCell>
                                    <TableCell className="align-top">
                                        <Label
                                            htmlFor={`line-${line.key}-price`}
                                            className="sr-only"
                                        >
                                            {`Price, line ${index + 1}`}
                                        </Label>
                                        <Input
                                            id={`line-${line.key}-price`}
                                            type="number"
                                            inputMode="decimal"
                                            min={0}
                                            step="0.0001"
                                            className="text-right"
                                            value={line.price ?? ''}
                                            onChange={(event) =>
                                                setLine(line.key, {
                                                    price:
                                                        event.target.value ===
                                                        ''
                                                            ? null
                                                            : Number(
                                                                  event.target
                                                                      .value,
                                                              ),
                                                })
                                            }
                                        />
                                        <InputError
                                            message={
                                                errors[`lines.${index}.price`]
                                            }
                                        />
                                    </TableCell>
                                    <TableCell className="flex align-top">
                                        <PurchaseHistoryDialog
                                            label={`Purchase history, line ${index + 1}`}
                                            product={line.label}
                                            supplierId={form.data.supplierId}
                                            currencyId={form.data.currencyId}
                                            productDetailId={
                                                line.productDetailId
                                            }
                                        />
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            aria-label={`Remove line ${index + 1}`}
                                            disabled={
                                                form.data.lines.length === 1
                                            }
                                            onClick={() =>
                                                form.setData(
                                                    'lines',
                                                    form.data.lines.filter(
                                                        (l) =>
                                                            l.key !== line.key,
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
                            ))}
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
                                    ...form.data.lines,
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

PricelistForm.layout = {
    breadcrumbs: [{ title: 'Pricelists', href: pricelistsIndex() }],
};
