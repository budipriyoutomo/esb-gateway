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
    index as indexRoute,
    show as showRoute,
    store as storeRoute,
    update as updateRoute,
} from '@/routes/menu-categories';
import type { MenuCategory } from '@/types/pos';

type Props = {
    category: MenuCategory | null;
    /** The list page the category was found on (edit only). */
    page: number | null;
};

type Detail = {
    key: number;
    /** The ERP's detail; null for a new one. */
    id: number | null;
    name: string;
    code: string;
    description: string;
    maxOrderQty: number | null;
    buttonColor: string;
};

type Account = 'salesAccount' | 'cogsAccount' | 'discountAccount';

type FormData = {
    name: string;
    code: string;
    salesAccount: string;
    cogsAccount: string;
    discountAccount: string;
    description: string;
    buttonColor: string;
    details: Detail[];
};

/** Mirrors SaveMenuCategoryRequest. */
const NAME_MAX = 50;
const ACCOUNT_MAX = 20;

const ACCOUNTS: { field: Account; label: string }[] = [
    { field: 'salesAccount', label: 'Sales account' },
    { field: 'cogsAccount', label: 'COGS account' },
    { field: 'discountAccount', label: 'Discount account' },
];

let nextKey = 0;

function emptyDetail(): Detail {
    nextKey += 1;

    return {
        key: nextKey,
        id: null,
        name: '',
        code: '',
        description: '',
        maxOrderQty: null,
        buttonColor: '',
    };
}

function initial(category: MenuCategory | null): FormData {
    if (category === null) {
        return {
            name: '',
            code: '',
            salesAccount: '',
            cogsAccount: '',
            discountAccount: '',
            description: '',
            buttonColor: '',
            details: [emptyDetail()],
        };
    }

    return {
        name: category.name,
        code: category.code ?? '',
        salesAccount: category.salesAccount ?? '',
        cogsAccount: category.cogsAccount ?? '',
        discountAccount: category.discountAccount ?? '',
        description: category.description ?? '',
        buttonColor: category.buttonColor ?? '',
        details: category.details.map((detail) => ({
            ...emptyDetail(),
            id: detail.id,
            name: detail.name,
            code: detail.code ?? '',
            description: detail.description ?? '',
            maxOrderQty: detail.maxOrderQty,
            buttonColor: detail.buttonColor ?? '',
        })),
    };
}

/** What the server's SaveMenuCategoryRequest takes. */
function payload(data: FormData, page: number | null) {
    return {
        ...(page === null ? {} : { page }),
        name: data.name,
        code: data.code,
        salesAccount: data.salesAccount,
        cogsAccount: data.cogsAccount,
        discountAccount: data.discountAccount,
        description: data.description,
        buttonColor: data.buttonColor,
        details: data.details.map(({ key: _key, ...detail }) => detail),
    };
}

/**
 * Create or edit a POS menu category ([ESB-048]). The accounts are picked
 * from the chart of accounts and sent as their descriptions, as the ERP
 * takes them. A current detail stays (the ERP's update cannot be told to
 * drop one); new details can be added.
 */
export default function MenuCategoryForm({ category, page }: Props) {
    const form = useForm<FormData>(initial(category));
    const errors = form.errors as Record<string, string | undefined>;
    const { data } = form;
    const title = category ? `Edit ${category.name}` : 'New menu category';
    const backHref = category
        ? showRoute(category.id, { query: { page: page ?? 1 } }).url
        : indexRoute().url;

    function setDetail(key: number, changes: Partial<Detail>) {
        form.setData(
            'details',
            data.details.map((detail) =>
                detail.key === key ? { ...detail, ...changes } : detail,
            ),
        );
    }

    function submit() {
        form.transform((current) => payload(current, category ? page : null));

        if (category) {
            form.put(updateRoute(category.id).url);
        } else {
            form.post(storeRoute().url);
        }
    }

    return (
        <>
            <Head title={title} />

            <RecordForm
                title={title}
                description="Saved in ESB OMS straight away."
                back={{
                    href: backHref,
                    label: category ? category.name : 'Menu categories',
                }}
                cancelHref={backHref}
                submitLabel={category ? 'Save changes' : 'Create category'}
                onSubmit={submit}
                processing={form.processing}
                isDirty={form.isDirty}
                formError={errors.form}
            >
                <div className="grid gap-4 sm:grid-cols-2">
                    <RecordFormField id="name" label="Name" error={errors.name}>
                        <Input
                            id="name"
                            required
                            maxLength={NAME_MAX}
                            value={data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                        />
                    </RecordFormField>
                    <RecordFormField
                        id="code"
                        label="Code (optional)"
                        error={errors.code}
                    >
                        <Input
                            id="code"
                            maxLength={NAME_MAX}
                            value={data.code}
                            onChange={(event) =>
                                form.setData('code', event.target.value)
                            }
                        />
                    </RecordFormField>
                    {ACCOUNTS.map(({ field, label }) => (
                        <div key={field} className="grid gap-2">
                            <LookupCombobox
                                id={field}
                                label={label}
                                kind="coa-accounts"
                                value={data[field] === '' ? null : data[field]}
                                selectedLabel={data[field] || null}
                                onChange={(option) =>
                                    // The ERP takes the account's description.
                                    form.setData(field, option?.label ?? '')
                                }
                                placeholder="Search accounts"
                            />
                            {data[field].length > ACCOUNT_MAX && (
                                <p className="text-muted-foreground text-xs">
                                    The ERP takes descriptions of at most{' '}
                                    {ACCOUNT_MAX} characters.
                                </p>
                            )}
                            <InputError message={errors[field]} />
                        </div>
                    ))}
                    <RecordFormField
                        id="buttonColor"
                        label="Button colour (optional, #rrggbb)"
                        error={errors.buttonColor}
                    >
                        <Input
                            id="buttonColor"
                            placeholder="#a4ff70"
                            value={data.buttonColor}
                            onChange={(event) =>
                                form.setData('buttonColor', event.target.value)
                            }
                        />
                    </RecordFormField>
                    <div className="sm:col-span-2">
                        <RecordFormField
                            id="description"
                            label="Description (optional)"
                            error={errors.description}
                        >
                            <Input
                                id="description"
                                value={data.description}
                                onChange={(event) =>
                                    form.setData(
                                        'description',
                                        event.target.value,
                                    )
                                }
                            />
                        </RecordFormField>
                    </div>
                </div>

                <div className="grid gap-3">
                    <Table aria-label="Category details">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead className="w-36">Code</TableHead>
                                <TableHead className="w-32 text-right">
                                    Max order
                                </TableHead>
                                <TableHead className="w-36">Colour</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead className="w-12">
                                    <span className="sr-only">Actions</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {data.details.map((detail, index) => {
                                const n = index + 1;
                                const error = (field: string) =>
                                    errors[`details.${index}.${field}`];
                                const cell = (
                                    field:
                                        | 'name'
                                        | 'code'
                                        | 'buttonColor'
                                        | 'description',
                                    label: string,
                                ) => (
                                    <>
                                        <Label
                                            htmlFor={`detail-${detail.key}-${field}`}
                                            className="sr-only"
                                        >
                                            {`${label}, row ${n}`}
                                        </Label>
                                        <Input
                                            id={`detail-${detail.key}-${field}`}
                                            maxLength={
                                                field === 'description'
                                                    ? undefined
                                                    : NAME_MAX
                                            }
                                            value={detail[field]}
                                            onChange={(event) =>
                                                setDetail(detail.key, {
                                                    [field]: event.target.value,
                                                })
                                            }
                                        />
                                        <InputError message={error(field)} />
                                    </>
                                );

                                return (
                                    <TableRow key={detail.key}>
                                        <TableCell className="align-top">
                                            {cell('name', 'Detail name')}
                                        </TableCell>
                                        <TableCell className="align-top">
                                            {cell('code', 'Detail code')}
                                        </TableCell>
                                        <TableCell className="align-top">
                                            <Label
                                                htmlFor={`detail-${detail.key}-max`}
                                                className="sr-only"
                                            >
                                                {`Max order, row ${n}`}
                                            </Label>
                                            <Input
                                                id={`detail-${detail.key}-max`}
                                                type="number"
                                                inputMode="decimal"
                                                min={0}
                                                step="0.0001"
                                                className="text-right"
                                                value={detail.maxOrderQty ?? ''}
                                                onChange={(event) =>
                                                    setDetail(detail.key, {
                                                        maxOrderQty:
                                                            event.target
                                                                .value === ''
                                                                ? null
                                                                : Number(
                                                                      event
                                                                          .target
                                                                          .value,
                                                                  ),
                                                    })
                                                }
                                            />
                                            <InputError
                                                message={error('maxOrderQty')}
                                            />
                                        </TableCell>
                                        <TableCell className="align-top">
                                            {cell(
                                                'buttonColor',
                                                'Detail colour',
                                            )}
                                        </TableCell>
                                        <TableCell className="align-top">
                                            {cell(
                                                'description',
                                                'Detail description',
                                            )}
                                        </TableCell>
                                        <TableCell className="align-top">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Remove detail ${n}`}
                                                title={
                                                    detail.id === null
                                                        ? undefined
                                                        : 'Remove a current detail in the ERP'
                                                }
                                                disabled={
                                                    detail.id !== null ||
                                                    data.details.length === 1
                                                }
                                                onClick={() =>
                                                    form.setData(
                                                        'details',
                                                        data.details.filter(
                                                            (d) =>
                                                                d.key !==
                                                                detail.key,
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
                    <InputError message={errors.details} />
                    <div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                form.setData('details', [
                                    ...data.details,
                                    emptyDetail(),
                                ])
                            }
                        >
                            <Plus className="size-4" aria-hidden />
                            Add detail
                        </Button>
                    </div>
                </div>
            </RecordForm>
        </>
    );
}
