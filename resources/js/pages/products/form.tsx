import { Head, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import InputError from '@/components/input-error';
import { LookupCombobox } from '@/components/lookup-combobox';
import { RecordForm, RecordFormField } from '@/components/record-form';
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
    index as productsIndex,
    show as productsShow,
    store as storeRoute,
    update as updateRoute,
} from '@/routes/products';
import type { ProductDetail } from '@/types/master-data';

type UnitRow = {
    key: number;
    productDetailId: number | null;
    uomId: number | null;
    uomName: string | null;
    qty: number | null;
    isBase: boolean;
    purchasable: boolean;
    saleable: boolean;
    stocked: boolean;
    transferable: boolean;
    sku: string;
    basePrice: number | null;
    weight: number | null;
    active: boolean;
};

type Flag = 'purchasable' | 'saleable' | 'requestable' | 'vat' | 'luxury';

type FormData = Record<Flag, boolean> & {
    name: string;
    code: string;
    categoryId: number | null;
    subCategoryId: number | null;
    notes: string;
    receiptTolerance: number | null;
    units: UnitRow[];
};

const FLAGS: { key: Flag; label: string }[] = [
    { key: 'purchasable', label: 'Purchasable' },
    { key: 'saleable', label: 'Saleable' },
    { key: 'requestable', label: 'Requestable' },
    { key: 'vat', label: 'VAT' },
    { key: 'luxury', label: 'Luxury item' },
];

const UNIT_FLAGS: {
    key: 'purchasable' | 'saleable' | 'stocked' | 'transferable' | 'active';
    label: string;
}[] = [
    { key: 'purchasable', label: 'Purchase' },
    { key: 'saleable', label: 'Sales' },
    { key: 'stocked', label: 'Stock' },
    { key: 'transferable', label: 'Transfer' },
    { key: 'active', label: 'Active' },
];

let nextKey = 0;

function newUnit(isBase: boolean): UnitRow {
    nextKey += 1;

    return {
        key: nextKey,
        productDetailId: null,
        uomId: null,
        uomName: null,
        qty: isBase ? 1 : null,
        isBase,
        purchasable: true,
        saleable: false,
        stocked: true,
        transferable: true,
        sku: '',
        basePrice: null,
        weight: null,
        active: true,
    };
}

function initial(product: ProductDetail | null): FormData {
    return {
        name: product?.name ?? '',
        code: product?.code ?? '',
        categoryId: product?.categoryId ?? null,
        subCategoryId: product?.subCategoryId ?? null,
        notes: product?.notes ?? '',
        purchasable: product?.purchasable ?? true,
        saleable: product?.saleable ?? false,
        requestable: product?.requestable ?? true,
        vat: product?.vat ?? false,
        luxury: product?.luxury ?? false,
        receiptTolerance: product?.receiptTolerance ?? null,
        units: product
            ? product.units.map((unit) => ({
                  ...newUnit(false),
                  productDetailId: unit.productDetailId,
                  uomId: unit.uomId,
                  uomName: unit.uomName,
                  qty: unit.qty,
                  isBase: unit.isBase === true,
                  purchasable: unit.purchasable === true,
                  saleable: unit.saleable === true,
                  stocked: unit.stocked === true,
                  transferable: unit.transferable === true,
                  sku: unit.sku ?? '',
                  basePrice: unit.basePrice,
                  weight: unit.weight,
                  active: unit.active !== false,
              }))
            : [newUnit(true)],
    };
}

function numberOrNull(value: string): number | null {
    return value === '' ? null : Number(value);
}

/**
 * Create or edit a product and its units ([ESB-031], S3). The ERP may hold
 * a new product for approval. On an update the gateway carries over what
 * this form does not edit (BOM, CoreTax code, custom fields, menus).
 */
export default function ProductForm({
    product,
}: {
    product: ProductDetail | null;
}) {
    const form = useForm<FormData>(initial(product));
    const errors = form.errors as Record<string, string | undefined>;
    const title = product ? `Edit ${product.name || 'product'}` : 'New product';

    function setUnit(key: number, changes: Partial<UnitRow>) {
        form.setData(
            'units',
            form.data.units.map((unit) => {
                if (unit.key === key) {
                    return { ...unit, ...changes };
                }

                // One base unit: choosing one clears the others.
                return changes.isBase === true
                    ? { ...unit, isBase: false }
                    : unit;
            }),
        );
    }

    function submit() {
        form.transform((data) => ({
            ...data,
            units: data.units.map(
                ({ key: _key, uomName: _name, ...unit }) => unit,
            ),
        }));

        if (product) {
            form.put(updateRoute(product.id).url);
        } else {
            form.post(storeRoute().url);
        }
    }

    return (
        <>
            <Head title={title} />

            <RecordForm
                title={title}
                back={
                    product
                        ? {
                              href: productsShow(product.id),
                              label: product.name || 'Product',
                          }
                        : { href: productsIndex(), label: 'Products' }
                }
                cancelHref={
                    product ? productsShow(product.id) : productsIndex()
                }
                submitLabel={product ? 'Save changes' : 'Create product'}
                onSubmit={submit}
                processing={form.processing}
                isDirty={form.isDirty}
                formError={errors.form}
            >
                <div className="grid gap-4 sm:grid-cols-[1fr_12rem]">
                    <RecordFormField id="name" label="Name" error={errors.name}>
                        <Input
                            id="name"
                            value={form.data.name}
                            maxLength={100}
                            required
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                        />
                    </RecordFormField>
                    <RecordFormField id="code" label="Code" error={errors.code}>
                        <Input
                            id="code"
                            value={form.data.code}
                            maxLength={50}
                            onChange={(event) =>
                                form.setData('code', event.target.value)
                            }
                        />
                    </RecordFormField>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <LookupCombobox
                            id="categoryId"
                            label="Category"
                            kind="product-categories"
                            value={
                                form.data.categoryId === null
                                    ? null
                                    : String(form.data.categoryId)
                            }
                            selectedLabel={product?.categoryName}
                            onChange={(option) =>
                                form.setData(
                                    'categoryId',
                                    option ? Number(option.value) : null,
                                )
                            }
                            placeholder="Choose a category"
                        />
                        <InputError message={errors.categoryId} />
                    </div>
                    <div className="grid gap-2">
                        <LookupCombobox
                            id="subCategoryId"
                            label="Sub-category"
                            kind="product-sub-categories"
                            value={
                                form.data.subCategoryId === null
                                    ? null
                                    : String(form.data.subCategoryId)
                            }
                            selectedLabel={product?.subCategoryName}
                            onChange={(option) =>
                                form.setData(
                                    'subCategoryId',
                                    option ? Number(option.value) : null,
                                )
                            }
                            placeholder="Choose a sub-category"
                        />
                        <InputError message={errors.subCategoryId} />
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-[1fr_12rem]">
                    <RecordFormField
                        id="notes"
                        label="Notes"
                        error={errors.notes}
                    >
                        <Input
                            id="notes"
                            value={form.data.notes}
                            maxLength={100}
                            onChange={(event) =>
                                form.setData('notes', event.target.value)
                            }
                        />
                    </RecordFormField>
                    <RecordFormField
                        id="receiptTolerance"
                        label="Receipt tolerance"
                        error={errors.receiptTolerance}
                    >
                        <Input
                            id="receiptTolerance"
                            type="number"
                            inputMode="decimal"
                            min={0}
                            step="any"
                            value={form.data.receiptTolerance ?? ''}
                            onChange={(event) =>
                                form.setData(
                                    'receiptTolerance',
                                    numberOrNull(event.target.value),
                                )
                            }
                        />
                    </RecordFormField>
                </div>

                <fieldset className="flex flex-wrap gap-x-6 gap-y-3">
                    <legend className="mb-2 text-base font-medium">
                        Product flags
                    </legend>
                    {FLAGS.map(({ key, label }) => (
                        <div key={key} className="flex items-center gap-2">
                            <Checkbox
                                id={key}
                                checked={form.data[key]}
                                onCheckedChange={(checked) =>
                                    form.setData(key, checked === true)
                                }
                            />
                            <Label htmlFor={key}>{label}</Label>
                        </div>
                    ))}
                </fieldset>

                <div className="grid gap-3">
                    <h2 className="text-base font-medium">Units</h2>
                    <div className="overflow-x-auto">
                        <Table aria-label="Units">
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="min-w-44">
                                        Unit
                                    </TableHead>
                                    <TableHead className="w-24">Qty</TableHead>
                                    <TableHead>Base</TableHead>
                                    <TableHead>Used for</TableHead>
                                    <TableHead className="min-w-32">
                                        SKU
                                    </TableHead>
                                    <TableHead className="w-32">
                                        Base price
                                    </TableHead>
                                    <TableHead className="w-24">
                                        Weight
                                    </TableHead>
                                    <TableHead className="w-12">
                                        <span className="sr-only">Actions</span>
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {form.data.units.map((unit, index) => {
                                    const line = index + 1;

                                    return (
                                        <TableRow key={unit.key}>
                                            <TableCell className="align-top">
                                                <LookupCombobox
                                                    id={`unit-${unit.key}-uom`}
                                                    label={`Unit, line ${line}`}
                                                    kind="units"
                                                    value={
                                                        unit.uomId === null
                                                            ? null
                                                            : String(unit.uomId)
                                                    }
                                                    selectedLabel={unit.uomName}
                                                    onChange={(option) =>
                                                        setUnit(unit.key, {
                                                            uomId: option
                                                                ? Number(
                                                                      option.value,
                                                                  )
                                                                : null,
                                                            uomName:
                                                                option?.label ??
                                                                null,
                                                        })
                                                    }
                                                    placeholder="Choose"
                                                />
                                                <InputError
                                                    message={
                                                        errors[
                                                            `units.${index}.uomId`
                                                        ]
                                                    }
                                                />
                                            </TableCell>
                                            <TableCell className="align-top">
                                                <Input
                                                    aria-label={`Qty, line ${line}`}
                                                    type="number"
                                                    inputMode="decimal"
                                                    min={0}
                                                    step="any"
                                                    value={unit.qty ?? ''}
                                                    onChange={(event) =>
                                                        setUnit(unit.key, {
                                                            qty: numberOrNull(
                                                                event.target
                                                                    .value,
                                                            ),
                                                        })
                                                    }
                                                />
                                                <InputError
                                                    message={
                                                        errors[
                                                            `units.${index}.qty`
                                                        ]
                                                    }
                                                />
                                            </TableCell>
                                            <TableCell className="align-top">
                                                <Checkbox
                                                    aria-label={`Base unit, line ${line}`}
                                                    checked={unit.isBase}
                                                    onCheckedChange={(
                                                        checked,
                                                    ) =>
                                                        setUnit(unit.key, {
                                                            isBase:
                                                                checked ===
                                                                true,
                                                        })
                                                    }
                                                />
                                            </TableCell>
                                            <TableCell className="align-top">
                                                <div className="flex flex-wrap gap-x-3 gap-y-1">
                                                    {UNIT_FLAGS.map(
                                                        ({ key, label }) => (
                                                            <label
                                                                key={key}
                                                                className="flex items-center gap-1 text-sm"
                                                            >
                                                                <Checkbox
                                                                    aria-label={`${label}, line ${line}`}
                                                                    checked={
                                                                        unit[
                                                                            key
                                                                        ]
                                                                    }
                                                                    onCheckedChange={(
                                                                        checked,
                                                                    ) =>
                                                                        setUnit(
                                                                            unit.key,
                                                                            {
                                                                                [key]:
                                                                                    checked ===
                                                                                    true,
                                                                            },
                                                                        )
                                                                    }
                                                                />
                                                                {label}
                                                            </label>
                                                        ),
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell className="align-top">
                                                <Input
                                                    aria-label={`SKU, line ${line}`}
                                                    value={unit.sku}
                                                    maxLength={50}
                                                    onChange={(event) =>
                                                        setUnit(unit.key, {
                                                            sku: event.target
                                                                .value,
                                                        })
                                                    }
                                                />
                                                <InputError
                                                    message={
                                                        errors[
                                                            `units.${index}.sku`
                                                        ]
                                                    }
                                                />
                                            </TableCell>
                                            <TableCell className="align-top">
                                                <Input
                                                    aria-label={`Base price, line ${line}`}
                                                    type="number"
                                                    inputMode="decimal"
                                                    min={0}
                                                    step="any"
                                                    className="text-right"
                                                    value={unit.basePrice ?? ''}
                                                    onChange={(event) =>
                                                        setUnit(unit.key, {
                                                            basePrice:
                                                                numberOrNull(
                                                                    event.target
                                                                        .value,
                                                                ),
                                                        })
                                                    }
                                                />
                                            </TableCell>
                                            <TableCell className="align-top">
                                                <Input
                                                    aria-label={`Weight, line ${line}`}
                                                    type="number"
                                                    inputMode="decimal"
                                                    min={0}
                                                    step="any"
                                                    className="text-right"
                                                    value={unit.weight ?? ''}
                                                    onChange={(event) =>
                                                        setUnit(unit.key, {
                                                            weight: numberOrNull(
                                                                event.target
                                                                    .value,
                                                            ),
                                                        })
                                                    }
                                                />
                                            </TableCell>
                                            <TableCell className="align-top">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label={`Remove line ${line}`}
                                                    disabled={
                                                        form.data.units
                                                            .length === 1
                                                    }
                                                    onClick={() =>
                                                        form.setData(
                                                            'units',
                                                            form.data.units.filter(
                                                                (u) =>
                                                                    u.key !==
                                                                    unit.key,
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
                    </div>
                    <InputError message={errors.units} />
                    <div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                form.setData('units', [
                                    ...form.data.units,
                                    newUnit(false),
                                ])
                            }
                        >
                            <Plus className="size-4" aria-hidden />
                            Add unit
                        </Button>
                    </div>
                </div>
            </RecordForm>
        </>
    );
}

ProductForm.layout = {
    breadcrumbs: [{ title: 'Products', href: productsIndex() }],
};
