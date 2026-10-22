import { Head, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { LookupCombobox } from '@/components/lookup-combobox';
import { RecordForm, RecordFormField } from '@/components/record-form';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    index as categoriesIndex,
    show as categoriesShow,
    store as storeRoute,
    update as updateRoute,
} from '@/routes/product-categories';
import type { ProductCategory } from '@/types/master-data';

type AccountKey =
    | 'coaNo'
    | 'cogsCoaNo'
    | 'opnameCoaNo'
    | 'productSalesCoaNo'
    | 'accrualBasisCoaNo'
    | 'depreciationCoaNo'
    | 'depreciationExpenseCoaNo';

type NumberKey =
    | 'depreciationMonths'
    | 'fiscalDepreciationMonths'
    | 'depreciationTypeId'
    | 'fiscalDepreciationTypeId';

type FormData = Record<AccountKey, string | null> &
    Record<NumberKey, number | null> & {
        name: string;
        code: string;
        typeId: number | null;
        notes: string;
        assetTax: boolean;
    };

const ACCOUNTS: { key: AccountKey; label: string }[] = [
    { key: 'coaNo', label: 'Inventory account' },
    { key: 'cogsCoaNo', label: 'COGS account' },
    { key: 'opnameCoaNo', label: 'Stock opname account' },
    { key: 'productSalesCoaNo', label: 'Product sales account' },
    { key: 'accrualBasisCoaNo', label: 'Accrual basis account' },
];

const DEPRECIATION_ACCOUNTS: { key: AccountKey; label: string }[] = [
    { key: 'depreciationCoaNo', label: 'Accumulated depreciation account' },
    { key: 'depreciationExpenseCoaNo', label: 'Depreciation expense account' },
];

const DEPRECIATION_NUMBERS: { key: NumberKey; label: string; hint?: string }[] =
    [
        { key: 'depreciationMonths', label: 'Length (months)' },
        { key: 'fiscalDepreciationMonths', label: 'Fiscal length (months)' },
        {
            key: 'depreciationTypeId',
            label: 'Method id',
            hint: 'The ERP documents no list of methods; enter its id.',
        },
        { key: 'fiscalDepreciationTypeId', label: 'Fiscal method id' },
    ];

/**
 * Create or edit a product category with the accounts it posts to
 * ([ESB-030], S3). The update replaces the whole category, so every field is
 * sent back, changed or not.
 */
export default function ProductCategoryForm({
    category,
}: {
    category: ProductCategory | null;
}) {
    const form = useForm<FormData>({
        name: category?.name ?? '',
        code: category?.code ?? '',
        typeId: category?.typeId ?? null,
        notes: category?.notes ?? '',
        coaNo: category?.coaNo ?? null,
        cogsCoaNo: category?.cogsCoaNo ?? null,
        opnameCoaNo: category?.opnameCoaNo ?? null,
        productSalesCoaNo: category?.productSalesCoaNo ?? null,
        accrualBasisCoaNo: category?.accrualBasisCoaNo ?? null,
        depreciationCoaNo: category?.depreciationCoaNo ?? null,
        depreciationExpenseCoaNo: category?.depreciationExpenseCoaNo ?? null,
        depreciationMonths: category?.depreciationMonths ?? null,
        fiscalDepreciationMonths: category?.fiscalDepreciationMonths ?? null,
        depreciationTypeId: category?.depreciationTypeId ?? null,
        fiscalDepreciationTypeId: category?.fiscalDepreciationTypeId ?? null,
        assetTax: category?.assetTax ?? false,
    });
    const errors = form.errors as Partial<
        Record<keyof FormData | 'form', string>
    >;
    const title = category
        ? `Edit ${category.name || 'category'}`
        : 'New category';

    function submit() {
        if (category) {
            form.put(updateRoute(category.id).url);
        } else {
            form.post(storeRoute().url);
        }
    }

    function account({ key, label }: { key: AccountKey; label: string }) {
        return (
            <div key={key} className="grid gap-2">
                <LookupCombobox
                    id={key}
                    label={label}
                    kind="coa-accounts"
                    value={form.data[key]}
                    selectedLabel={category?.[key] ?? null}
                    onChange={(option) =>
                        form.setData(key, option ? option.value : null)
                    }
                    placeholder="No account"
                />
                <InputError message={errors[key]} />
            </div>
        );
    }

    return (
        <>
            <Head title={title} />

            <RecordForm
                title={title}
                back={
                    category
                        ? {
                              href: categoriesShow(category.id),
                              label: category.name || 'Category',
                          }
                        : { href: categoriesIndex(), label: 'Categories' }
                }
                cancelHref={
                    category ? categoriesShow(category.id) : categoriesIndex()
                }
                submitLabel={category ? 'Save changes' : 'Create category'}
                onSubmit={submit}
                processing={form.processing}
                isDirty={form.isDirty}
                formError={errors.form}
            >
                <div className="grid gap-4 sm:grid-cols-[1fr_8rem]">
                    <RecordFormField id="name" label="Name" error={errors.name}>
                        <Input
                            id="name"
                            value={form.data.name}
                            maxLength={50}
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
                            maxLength={3}
                            onChange={(event) =>
                                form.setData('code', event.target.value)
                            }
                        />
                    </RecordFormField>
                </div>

                <div className="grid gap-2">
                    <LookupCombobox
                        id="typeId"
                        label="Type"
                        kind="category-types"
                        value={
                            form.data.typeId === null
                                ? null
                                : String(form.data.typeId)
                        }
                        selectedLabel={category?.typeName ?? null}
                        onChange={(option) =>
                            form.setData(
                                'typeId',
                                option ? Number(option.value) : null,
                            )
                        }
                        placeholder="Choose a type"
                    />
                    <InputError message={errors.typeId} />
                </div>

                <RecordFormField id="notes" label="Notes" error={errors.notes}>
                    <Input
                        id="notes"
                        value={form.data.notes}
                        maxLength={100}
                        onChange={(event) =>
                            form.setData('notes', event.target.value)
                        }
                    />
                </RecordFormField>

                <fieldset className="grid gap-4">
                    <legend className="mb-2 text-base font-medium">
                        Accounts
                    </legend>
                    {ACCOUNTS.map(account)}
                </fieldset>

                <fieldset className="grid gap-4">
                    <legend className="mb-2 text-base font-medium">
                        Depreciation (asset categories)
                    </legend>
                    {DEPRECIATION_ACCOUNTS.map(account)}
                    <div className="grid gap-4 sm:grid-cols-2">
                        {DEPRECIATION_NUMBERS.map(({ key, label, hint }) => (
                            <RecordFormField
                                key={key}
                                id={key}
                                label={label}
                                hint={hint}
                                error={errors[key]}
                            >
                                <Input
                                    id={key}
                                    type="number"
                                    inputMode="numeric"
                                    min={0}
                                    value={form.data[key] ?? ''}
                                    onChange={(event) =>
                                        form.setData(
                                            key,
                                            event.target.value === ''
                                                ? null
                                                : Number(event.target.value),
                                        )
                                    }
                                />
                            </RecordFormField>
                        ))}
                    </div>
                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="assetTax"
                            checked={form.data.assetTax}
                            onCheckedChange={(checked) =>
                                form.setData('assetTax', checked === true)
                            }
                        />
                        <Label htmlFor="assetTax">Asset tax</Label>
                    </div>
                    <InputError message={errors.assetTax} />
                </fieldset>
            </RecordForm>
        </>
    );
}
